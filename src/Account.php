<?php
namespace Roi\PeerTransfer ;
// holds users
// add users
// update users
// delete users
class Account {
  // list of users we have
  private $users = [] ;

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
        'amount'=>0
      ] ;

      return true ;
    }
    throw new \Exception("invalid user id", 1);
  }

  function get(string $uid){
    return array_key_exists($uid, $this->users) ? $this->users[$uid] : null ;
  }

  function deposit(string $uid, float $amount) : bool{
    if($this->get($uid)){
      $this->throwWhenAmountIsNegative($amount) ;
      $this->users[$uid]['amount'] += $amount ;
      return true ;
    }

    return false ;
  }

  function sendMoneyToPeer(string $from, string $to, float $amount) : bool{
    if(!$this->get($from))
      throw new \Exception("Sender does not exist", 1);

    if(! $this->get($to))
      throw new \Exception("Recipient does not exist", 1);

    $this->throwWhenAmountIsNegative($amount) ;

    if(! $this->canMoveFunds($from, $amount))
      throw new \Exception("Insufficient fund") ;

    // deduct amount from senders balance
    $this->users[$from]['amount'] -= $amount ;
    // add amount to receivers account
    $this->users[$to]['amount']+= $amount ;

    return true ;
  }

  function transferOutOfApp(string $uid, float $amount) : bool {
    if(!$this->get($uid)){
      throw new \Exception("invalid user id", 1);
    }

    $this->throwWhenAmountIsNegative($amount) ;

    if(! $this->canMoveFunds($uid, $amount))
      throw new \Exception("Insufficient fund") ;
    // transferred out
    $this->users[$uid]['amount'] -= $amount ;

    return true ;
  }

  protected function throwWhenAmountIsNegative(float $amount, string $str = ''){
    if(!$this->isAmountNonNegative($amount))
      throw new \Exception($str ? $str : "Amount is expected to be a non-negative floating number") ;
  }

  protected function isAmountNonNegative(float $amount) : bool {
    return $amount>=0 ;
  }
  // expected that the user had been checked to exist before calling this class
  protected function canMoveFunds(string $uid, float $amount) : bool{
    return $this->users[$uid]['amount'] >= $amount ;
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
