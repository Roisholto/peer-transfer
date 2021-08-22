# Peer-transfer

An in memory peer to peer money transfer backend in PHP

### Installation

Clone the repo to get started.

cd to the project directory.

### Namespace

`\Roi\PeerTransfer`

### Usage

include the `autoload` file at the top of your script

`$Account = \Roi\PeerTransfer\Account::getInstance()`

### Methods

* `add(string $uid)`- creates a new user.

  ```php
  $Account->add('userA') ; // returns true
  $Account->add('userB') ; // returns true
  ```



* `get(string $uid)` - retrieves an account record.

  ```
  $Account->get('userA') ; // returns ['name'=>'userA', 'amount'=>0, 'currency'=>'USD'];
  ```



* `deposit(string $uid, float $amount, $currency)` - deposits `$amount` into `$user` account. For example

  ```php
  $Account->deposit('victor', 200, 'USD') ; // returns true
  $Account->get('victor') // return ['name'=>'victor', 'amount'=>200.00, 'balance'=>['USD'=>200, 'NGN'=>0, 'YUAN'=>0, 'YEN'=>0] ;
  ```

* `sendMoneyToPeer(string $from, string $to, float $amount, $currency)`- Make a transfer between peers on the platform.

* `transferOutOfApp(string $uid, float $amount, $currency)` - Make a transfer from a user on the platform to a third party account.

### Tests

Test cases can be found in the `./tests` directory in the project root.

Install dev dependencies in order to run the tests. Run `composer install --dev`  in the root of the project. Next run `./vendor/bin/phpunit tests` to see the test results.



### TODO

- [ ] Fix non USD-currency conversions, for example NGN to YUAN. There is a margin of error in the calculation when switching back from the YUAN to NGN.
- [ ] Make a Go implementation of this project
