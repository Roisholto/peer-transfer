<?php
namespace Roi\PeerTransfer ;
// holds users
// add users
// update users
// delete users
class Account {
  // list of users we have
  private $users = [] ;

  // stores the users balance for each currency
  // should be of the form ['userid'=>['USD'=>0, 'NGN'=> 0, 'YEN'=>0, 'yuan'=>0]]
  private $holdings = [] ;

  private const SUPPORTED_CURRENCIES = [
    'USD'=>1,
    'NGN'=>411.57,
    'YUAN'=>109.47,
    'YEN'=>6.46
    ] ;


  private static $instance = null;

  // The constructor is private
  // to prevent initiation with outer code.
  private function __construct()
  {
    // The expensive process (e.g.,db connection) goes here.
  }

  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Account();
    }

    return self::$instance;
  }

  // create an account with an inital deposit
  // $username is unique, though a string
  function add(string $uid) : bool {
    $uid = trim($uid);

    if(strlen($uid)){
      if($this->get($uid))
        throw new \Exception("User exists already", 1);

      $this->users[$uid] = [
        'name'=>$uid,
        'balance'=>[
          'USD'=>0,
          'NGN'=>0,
          'YUAN'=>0,
          'YEN'=>0
          ]
        ] ;

      return true ;
    }
    throw new \Exception("invalid user id", 1);
  }

  function get(string $uid){
    return array_key_exists($uid, $this->users) ? $this->users[$uid] : null ;
  }

  function deposit(string $uid, float $amount, string $currency) : bool{
    if($this->get($uid)){
      $this->throwWhenAmountIsNegative($amount) ;
      $this->throwIfCurrencyNotSupported($currency) ;

      $this->users[$uid]['balance'][$currency] += $amount ;
      return true ;
    }

    return false ;
  }

  // user specified sort model ;
  function getCurrencySortModel(){

  }

  function getSendersBalanceAfterTransfer(string $from, float $amount, string  $payee_currency) : array {
    // check if the user has the amount in this currency
    // if not get the amounts in the other accounts ;
    // calculate the rate in the receiver's currency ;
    // send in the receiver's currency.
    // deduct in each sender's currencies ;

    $balance = $this->get($from)['balance'] ;
    $senders_current_balance = $balance[$payee_currency] ;
    $new_senders_balance = [] ;
    if($senders_current_balance < $amount){
      // then i need to check the other balances ;
      $amount_left_to_makeup_payees_balance = $amount - $senders_current_balance ;
      $new_senders_balance[$payee_currency] = 0 ;
      /* $converted_rates below is an associative array of currency to value in payees currency ;
      * e.g if $amount is 2.00 and currency is USD, and the senders balance looks like
      * [NGN=>200.00, 'YUAN'=>0.00, 'USD'=>1.00, 'YEN'=> 1500.00]
      * then $converted_rates will look like  [NGN=>200/411.57, YUAN=>0, USD=>1.00 'YEN'=> 1500/6.46] ;
      */
      $converted_rates = []  ;

      foreach($balance as $key=> $value){
        if($key != $payee_currency){
          $converted_rates[$key] = static::convertCurrency($value, $key, $payee_currency) ;
        }
      }
      // now if the sum of the converted_rates is greater than the amount left,
      // then we can continue with the operation else abort ;
      if(array_sum($converted_rates) >= $amount_left_to_makeup_payees_balance){
        // the order of deductions in the wallets is from the wallet with the highest
        // value to the least.
        arsort($converted_rates) ;

        foreach($converted_rates as $key => $val) {
          $amount_left_to_makeup_payees_balance -= $val ;
          if($amount_left_to_makeup_payees_balance >= 0){
            // $this->users['balance'][$key] = 0 ;
            $new_senders_balance[$key] = 0 ;
            }
          else{
            $new_senders_balance[$key] = static::MoneyFormat(
              static::convertCurrency(
                abs($amount_left_to_makeup_payees_balance),
                $payee_currency,
                $key
                )
              ) ;
            break ;
          }
        }
      }
      else{
        throw new \Exception("Insufficient fund in wallets", 1);
      }
    }
    else{
      // sender has enough in wallet ;
      $new_senders_balance[$payee_currency] = $senders_current_balance - $amount ;
    }

    return $new_senders_balance ;
  }

  static function convertCurrency(float $amount, string $from, string $to) : float {
    $rate_from = static::SUPPORTED_CURRENCIES[$from] ;
    $rate_to = static::SUPPORTED_CURRENCIES[$to] ;
    $amount = abs($amount) ;
    if($to != 'USD' and $from !='USD'){
      // first get the rate in usd
      // then convert to the $to currency;
      $rate_in_dollar = static::convertCurrency($amount, $from, 'USD');
      return static::convertCurrency($rate_in_dollar, 'USD', $to) ;
    }

    $rate = ($amount*$rate_to)/$rate_from ;
    return $rate ;
  }

  static function moneyFormat($n){
    return number_format($n, 2, '.', '') ;
  }

  // @param $currency implies the the receivers currency
  function sendMoneyToPeer(string $from, string $to, float $amount, string $currency) : bool{
    if(!$this->get($from))
      throw new \Exception("Sender does not exist", 1);

    if(! $this->get($to))
      throw new \Exception("Recipient does not exist", 1);

    $this->throwWhenAmountIsNegative($amount) ;

    $this->throwIfCurrencyNotSupported($currency) ;

    $senders_balance_image = $this->getSendersBalanceAfterTransfer($from, $amount, $currency) ;

    // deduct amount from senders balance
    $this->users[$from]['balance'] = array_merge(
      $this->users[$from]['balance'],
      $senders_balance_image
      ) ;

    // add amount to receivers account
    $this->users[$to]['balance'][$currency]+= $amount ;

    return true ;
  }

  function transferOutOfApp(string $uid, float $amount, string $currency) : bool {
    if(!$this->get($uid)){
      throw new \Exception("invalid user id", 1);
    }

    $this->throwWhenAmountIsNegative($amount) ;

    $this->throwIfCurrencyNotSupported($currency) ;

    $balance_image = $this->getSendersBalanceAfterTransfer($uid, $amount, $currency) ;

    // transferred out
    $this->users[$uid]['balance'] = array_merge(
      $this->users[$uid]['balance'],
      $balance_image
    ) ;

    return true ;
  }

  protected function throwWhenAmountIsNegative(float $amount, string $str = ''){
    if(!$this->isAmountNonNegative($amount))
      throw new \Exception($str ? $str : "Amount is expected to be a non-negative floating number") ;
  }

  protected function isAmountNonNegative(float $amount) : bool {
    return $amount>=0 ;
  }

  protected function throwIfCurrencyNotSupported($currency){
    if(! $this->isCurrencySupported($currency))
      throw new \Exception("Currency not supported", 1);
  }

  protected function isCurrencySupported(string $currency) : bool{
    return array_key_exists($currency, static::SUPPORTED_CURRENCIES) ;
  }

  /**
   * Singletons should not be cloneable.
   */
  protected function __clone() { }

  /**
   * Singletons should not be restorable from strings.
   */
  public function __wakeup() {
    throw new \Exception("Cannot unserialize a singleton.");
  }
}
?>
