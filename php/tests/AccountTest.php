<?php
use PHPUnit\Framework\TestCase;
use Roi\PeerTransfer\Account ;

final class AccountTest extends TestCase {

  function testAdd() {
    $Account = Account::getInstance() ;
    $this->assertTrue($Account->add('victor')) ;
    $this->assertTrue($Account->add('darius')) ;

    return $Account ;
  }
  /**
  * @depends testAdd
  */
  function testAddDuplicateUserException($Account){
    $this->expectException(\Exception::class);
    $Account->add('victor') ;
  }
  /**
  * @depends testAdd
  */
  function testDeposit(){
    $Account = Account::getInstance() ;
    $this->assertTrue($Account->deposit('victor', 10, 'USD')) ;
    $this->assertTrue($Account->deposit('darius', 20, 'USD')) ;
    $this->assertTrue($Account->deposit('darius', 1000, 'NGN')) ;

    $this->assertEquals($Account->get('victor')['balance']['USD'], 10) ;
    $this->assertEquals($Account->get('darius')['balance']['USD'], 20) ;
    $this->assertEquals($Account->get('darius')['balance']['NGN'], 1000) ;

    return $Account ;
  }

  /**
  * @depends testDeposit
  */
  function testDepositException($Account){
    $this->expectException(\Exception::class) ;
    $Account->deposit('victor', -200, 'USD') ;
  }

  /**
  * @depends testDeposit
  */
  function testSendMoneyToPeer() {
    $Account = Account::getInstance() ;
    $this->assertTrue($Account->sendMoneyToPeer('darius', 'victor', 15, 'USD')) ;
    // The balance of victor is expected to be 25
    $this->assertEquals($Account->get('victor')['balance']['USD'], 25) ;
    $this->assertEquals($Account->get('darius')['balance']['USD'], 5) ;

    return $Account ;
  }

  /**
  * @depends testDeposit
  */
  function testSendMoneyToPeerException($Account){
    $this->expectException(\Exception::class) ;
    $Account->sendMoneyToPeer('darius', 'victor', 15, 'USD') ;
  }

  /**
  * @depends testSendMoneyToPeer
  */
  function testTransferOutOfApp(){
    $Account = Account::getInstance() ;
    // succesfully transfer ;
    $this->assertTrue($Account->transferOutOfApp('victor', 25, 'USD')) ;
    // confirm that the amount was deducted after transferred
    $this->assertEquals($Account->get('victor')['balance']['USD'], 0) ;
    return $Account ;
  }

  /**
  * @depends testDeposit
  */
  function testTransferMoneyToPeerNGNToUSD($Account){
    // get darius
    $darius_NGN = $Account->get('darius')['balance']['NGN'] ;
    $deducted_from_darius_NGN = Account::convertCurrency(2, 'USD', 'NGN') ;
    $this->assertTrue($Account->sendMoneyToPeer('darius', 'victor', 7, 'USD')) ;
    // though darius right now has just 5 dollar in his account
    // he is able to send 7 dollars because he has funds in his NGN account
    $this->assertEquals($Account->get('victor')['balance']['USD'], 7) ;
    $this->assertEquals($Account->get('darius')['balance']['USD'], 0) ;
    $this->assertEquals($Account->get('darius')['balance']['NGN'], $darius_NGN - $deducted_from_darius_NGN) ;

    return $Account ;
  }

  /**
  * @depends testTransferMoneyToPeerNGNToUSD
  */
  function testTransferMoneyOutUSDToNGN($Account){
    // transfer money to thirdparty in NGN from USD
    // currently victor has just 7 dollar in in his USD account
    $this->assertTrue($Account->transferOutOfApp('victor', 800, 'NGN')) ;

  }

  /**
  * @depends testDeposit
  */
  function testTransferMoneyFromYENToNGN($Account){
    $yen_to_ngn = Account::convertCurrency(1500.00, 'YEN', 'NGN') ;
    $Account->deposit('victor', 5000, 'YEN') ;
    // Ensure that victor has nothing left in his dollar account just to make the
    // the values more obvious.
    $Account->sendMoneyToPeer('victor', 'darius', 5.06, 'USD') ;
    $Account->sendMoneyToPeer('victor', 'darius', $yen_to_ngn, 'NGN') ;
    //
    $this->assertEquals($Account->get('darius')['balance']['USD'], 5.06) ;
    // be sure that the we have 2000 yen left in victors account ;
    $this->assertEquals($Account->get('victor')['balance']['YEN'], 3500, '', 0) ;
    // now ensure we have the naira equivalent of 3000 yen in darius account ;
    $this->assertEquals(
      $Account->get('darius')['balance']['NGN'],
      $yen_to_ngn
      ) ;
  }
  /**
  * @depends testTransferOutOfApp
  */
  function testTransferOutOfAppExceptionNegativeAmount($Account){
    // failed transfer becuase of negative amount
    $this->expectException(\Exception::class) ;
    $Account->transferOutOfApp('victor', -1005, 'USD') ;
  }

  /**
  * @depends testTransferOutOfApp
  */
  function testTransferOutOfAppExceptionInsufficientBalance($Account){
    // attempting to transfer more than is available ;
    $this->expectException(\Exception::class) ;
    $Account->transferOutOfApp('darius', 800, 'USD') ;
  }


  function testConvertNGNToUSD(){
    $a = Account::convertCurrency(1000, 'NGN', 'USD') ;
    $b = Account::convertCurrency($a, 'USD', 'NGN') ;
    $this->assertEquals(1000, $b) ;
  }

  function testConvertNGNToYUAN(){
    $a = Account::convertCurrency(3000, 'NGN', 'YUAN') ;
    $b = Account::convertCurrency($a, 'YUAN', 'NGN') ;
    $this->assertEquals(3000, $b) ;
  }

}

?>
