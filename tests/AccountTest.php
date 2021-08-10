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
    $this->assertTrue($Account->deposit('victor', 10)) ;
    $this->assertTrue($Account->deposit('darius', 20)) ;
    $this->assertEquals($Account->get('victor')['amount'], 10) ;
    $this->assertEquals($Account->get('darius')['amount'], 20) ;

    return $Account ;
  }

  /**
  * @depends testDeposit
  */
  function testDepositException($Account){
    $this->expectException(\Exception::class) ;
    $Account->deposit('victor', -200) ;
  }

  /**
  * @depends testDeposit
  */
  function testSendMoneyToPeer() {
    $Account = Account::getInstance() ;
    $this->assertTrue($Account->sendMoneyToPeer('darius', 'victor', 15)) ;
    // The balance of victor is expected to be 25
    $this->assertEquals($Account->get('victor')['amount'], 25) ;
    $this->assertEquals($Account->get('darius')['amount'], 5) ;
  }

  /**
  * @depends testDeposit
  */
  function testSendMoneyToPeerException($Account){
    $this->expectException(\Exception::class) ;
    $Account->sendMoneyToPeer('darius', 'victor', 15) ;
  }

  /**
  * @depends testSendMoneyToPeer
  */
  function testTransferOutOfApp(){
    $Account = Account::getInstance() ;
    // succesfully transfer ;
    $this->assertTrue($Account->transferOutOfApp('victor', 25)) ;
    // confirm that the amount was deducted after transferred
    $this->assertEquals($Account->get('victor')['amount'], 0) ;
    return $Account ;
  }

  /**
  * @depends testTransferOutOfApp
  */
  function testTransferOutOfAppExceptionNegativeAmount($Account){
    // failed transfer becuase of negative amount
    $this->expectException(\Exception::class) ;
    $Account->transferOutOfApp('victor', -25) ;
  }

  /**
  * @depends testTransferOutOfApp
  */
  function testTransferOutOfAppExceptionInsufficientBalance($Account){
    // attempting to transfer more than is available ;
    $this->expectException(\Exception::class) ;
    $Account->transferOutOfApp('darius', 200) ;
  }


  /**
  *
  */
  function getAccount(){

  }

}

?>
