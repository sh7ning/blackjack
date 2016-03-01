<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
if (isset($_GET['code']) && 'show' === $_GET['code']) {
    highlight_file(__FILE__);exit;
}
/**
 * 21点扑克牌
 * @copyright sh7ning 2015.3.30
 * @author sh7ning
 * @version 0.0.1
 */
class BlackJackCards {
    const ACE = 1;
    const JACK = 11;
    const QUEEN = 12;
    const KING = 13;

    private static $suits = array(
        'spades',   //黑桃
        'hearts',   //红桃
        'clubs',    //梅花
        'diamonds',  //方块
    );

    private static $_instance;
    private static $_deck = array();

    private function __construct() {
        $this->_buildPoker();
        $this->_shuffle();
    }
    private function __clone() {
    }
    public static function getInstance() {
        if(! (self::$_instance instanceof self) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    private function _buildPoker() {
        for ($face = 1; $face < 14; $face++) {
            foreach (self::$suits as $suit) {
                array_push(self::$_deck, array($face, $suit));
            }
        }
    }
    private function _shuffle() {
        shuffle(self::$_deck);  //洗牌
    }
    public function next() {
        return array_pop(self::$_deck);
    }
    public function getDeck() {
        return self::$_deck;
    }
}
class BlackJackHand {
    private $_player = '';
    private $_cards = array();  //当前准备的手牌
    private $_topScore = 0; //当前准备的手牌，最高分
    private $_idx = 0;  //只有用户才有
    private $_cardsObj = null;

    public function __sleep() {
        return array('_player', '_cards', '_topScore', '_idx');
    }

    public function __construct($player) {
        $this->_player = $player;
        $this->_cardsObj = BlackJackCards::getInstance();
        $this->_getHand();
    }
    /**
     * 发牌
     */
    private function _getHand() {
        array_push($this->_cards, $this->_cardsObj->next());
        $handStatus = self::getScore($this->_cards);
        if (!$handStatus[0]) {  //还未爆掉，获得爆掉为止,
            $this->_setTopScore($handStatus[1]);
            $this->_getHand();
        }
    }
    /**
     * 获取多余多少分数的扑克牌，庄家用
     */
    public function getHandOver($score) {
        $idxArr = array(); //之所以不用foreach是因为有A这个特殊的情况，导致前一刻大于，后一刻小于
        for ($this->_idx = 2, $len = count($this->_cards); $this->_idx < $len; $this->_idx++) {
            $totals = $this->getRealTotals();
            if ($totals[1] > $score) {
                // $GLOBALS['dbg']['idx'][] = array($this->_idx, $totals[1]);
                array_push($idxArr, $this->_idx);
            }
        }
        if (empty($idxArr)) BlackJack::joutput('denied');   //因为肯定有比他大的
        shuffle($idxArr);
        $this->_idx = array_pop($idxArr);
        return array_slice($this->_cards, 0, $this->_idx);
    }
    /**
     * 随意获取扑克牌
     * @param boolen $bust 是否不能爆掉
     */
    public function getAnyHand($bust = false) {
        if ($bust) {    //可以爆掉
            $this->_idx = rand(2, count($this->_cards));
        } else {
            $this->_idx = rand(2, count($this->_cards) - 1);
        }
        return array_slice($this->_cards, 0, $this->_idx);
    }
    /**
     * 获得下一张扑克牌
     */
    public function getNextHand() {
        if (count($this->_cards) > $this->_idx) {
            $card = array();
            $card = $this->_getHandByIdx($this->_idx);
            $this->_idx ++;
            return $card;
        }
        BlackJack::joutput('denied');
    }
    /**
     * 获得第几张扑克牌
     */
    private function _getHandByIdx($idx) {
        return isset($this->_cards[$idx]) ? $this->_cards[$idx] : false;
    }
    /**
     * 初始化手牌的时候设置可能出现的最高手牌得分
     */
    private function _setTopScore($score) {
        if ($score > $this->_topScore) {    // && $score < 22   因为不爆掉才设置所以不用判断
            $this->_topScore = $score;
        }
    }
    public function getTopScore() {
        return $this->_topScore;
    }
    public function showCards() {
        return $this->_cards;
    }
    static public function getScore($cards) {   //getTotals()
        $totals = self::getTotals($cards);

        //是否爆掉
        $bust = false;
        if ($totals[0] > 21) $bust = true;

        $score = self::getBiggerScore($totals);
        //手牌最高分
        return array($bust, $score);
    }
    public function getOneTotals() {    //获取第一张，一般用于庄家
            $cards = array_slice($this->_cards, 0, 1);    //已经被增加了
            $totals = self::getTotals($cards);
            $score = self::getBiggerScore($totals);
            return array(false, $score);
    }
    /**
     * 获取用户的当前所有的手牌之和
     */
    public function getRealTotals() {   //getTotals()
        if (count($this->_cards) >= $this->_idx) {
            $cards = array_slice($this->_cards, 0, $this->_idx);    //已经被增加了
            $totals = self::getTotals($cards);

            //手牌最高分
            $score = self::getBiggerScore($totals);
            //是否爆掉
            $bust = false;
            if ($totals[0] > 21) $bust = true;

            return array($bust, $score);
        }
        BlackJack::joutput('denied');   //基本运行不到这里
    }
    /** 
     * 获得更大的有效值
     * array(min, max)
     */
    static private function getBiggerScore($totals) {
        $score = $totals[0];
        if ($totals[0] != $totals[1]) {
            $score = max($totals);
            if ($score > 21) {
                $score = min($totals);
            }
        }
        return $score;
    }
    /**
     * 获得手牌的总分，
     * @param $cards array      like array(rank, suit)
     * @return array            like array(最小值, 最大值)
     */
    static private function getTotals($cards = array()) {
        $totals = array(0, 0);
        $accountedForAce = false;
        foreach ($cards as $card) { 
            switch ($card[0]) {
            case BlackJackCards::ACE:
                if ($accountedForAce) {
                    // already accounted for soft ace, just add one
                    $totals[0] += 1;
                    $totals[1] += 1;
                } else {
                    // account for soft ace - need to add 1 and 11 to totals
                    $totals[0] += 1;
                    $totals[1] += 11;
                    $accountedForAce = true;
                }
                break;
            case BlackJackCards::KING:
            case BlackJackCards::QUEEN:
            case BlackJackCards::JACK:
                $totals[0] += 10;
                $totals[1] += 10;
                break;
            default:
                $totals[0] += $card[0];
                $totals[1] += $card[0];
                break;
            }
        }
        return $totals;
    }
}
class BlackJack {
    const OVER_TIME = 3600;    //秒数，一个小时
    const APP_URL = "/club/blackjack.php";
    private $dealerHand = '';   //庄家手牌
    private $userHand = ''; //用户手牌
    private static $ret = array(
        'succ'  =>  0,
        'error' =>  1,
        'denied'    =>  2,
    );
    private $winCode = array(
        'win'   =>  1,
        'lose'  =>  2,
        'push'  =>  3,
    );
    private function isAjax() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH']) {
            return true;
        }
        return false;
    }
    public function __construct() {
        $do = empty($_POST['do']) ? 'run' : $_POST['do'];
        if (in_array($do, array('run', 'deal', 'hit', 'stand'))) {
            call_user_func_array(array($this, $do), array());
        } else {
            if ($this->isAjax()) {
                self::joutput('error');
            } else {
                header('location:http://' . self::APP_URL);
            }
        }
    }
    public function __destruct() {
        //存储手牌
        BlackSession::storeData(array(
            'dh'    =>  serialize($this->dealerHand), //系统手牌
            'uh'    =>  serialize($this->userHand), //用户手牌
            't'     =>  time(), //最后更新时间，超时则不进行操作
        ));
    }
    /**
     * 初始化界面
     */
    private function run() {
        include 'blackjack.html';
    }
    /**
     * 开始
     */
    private function deal() {
        $this->getHand(true);   //要牌
        $dealerHandCards = array($this->dealerHand->getNextHand());
        $userHandCards = array();
        array_push($userHandCards, $this->userHand->getNextHand());
        array_push($userHandCards, $this->userHand->getNextHand());

        $totals = $this->userHand->getRealTotals();

        self::joutput('succ', array(
            'dealer'    => array(
                self::showHand($dealerHandCards),
                $this->dealerHand->getOneTotals(),
            ),
            'user'      =>  array(
                self::showHand($userHandCards),
                $totals,
            ),
        ));
    }
    /**
     * 要牌
     */
    private function hit() {
        $this->getHand();   //要牌
        $dealerHandCards = array();
        $userHandCards = array($this->userHand->getNextHand());
        $totals = $this->userHand->getRealTotals();

        self::joutput('succ', array(
            'dealer'    => array(
                self::showHand($dealerHandCards),
                $this->dealerHand->getOneTotals(),
            ),
            'user'      =>  array(
                self::showHand($userHandCards),
                $totals,
            ),
        ));
    }
    /**
     * 停牌的时候展示庄家的手牌，只庄家用
     */
    private function getLeftDealer($totals) {
        $dealerHandScore = $this->dealerHand->getTopScore();
        $userHandScroe = $this->userHand->getTopScore();
        if ($dealerHandScore > $userHandScroe) {    //庄家不输
            if ($totals[0]) {   //目标扑克牌爆掉，随意，不爆掉即可
                return $this->dealerHand->getAnyHand(false);
            } else {    //大于等于分数而且不爆掉
                // $GLOBALS['dbg'] += array('over' =>  $totals[1]);
                return $this->dealerHand->getHandOver($totals[1]);
            }
        } else {
            return $this->dealerHand->getAnyHand(true);
        }
    }
    /**
     * 输赢情况
     */
    private function isWin($uT, $dT) {
        if ($dT[0]) return $this->winCode['win'];    //自己不会爆掉，爆掉不会停牌，
        if ($uT[1] < $dT[1])    return $this->winCode['lose'];  //输掉
        if ($uT[1] > $dT[1])    return $this->winCode['win'];   //赢
        if ($uT[1] == $dT[1])   return $this->winCode['push'];  //平局
    }
    /**
     * 停牌
     */
    private function stand() {
        $this->getHand();   //要牌

        $totals = $this->userHand->getRealTotals();
        $dealerHandCards = $this->getLeftDealer($totals); //按照输赢规则来确定应该输出的扑克牌情况
        $dealerHandTotals = $this->dealerHand->getRealTotals();

        self::joutput('succ', array(
            'dealer'    => array(
                self::showHand($dealerHandCards),
                $dealerHandTotals,
            ),
            'user'      =>  array(
                self::showHand(array()),
                $totals,
            ),
            'result'       =>  $this->isWin($totals, $dealerHandTotals),
            // 'u' =>  $this->showHand($this->userHand->showCards()),  //待完善，去掉，
            // 'd' =>  $this->showHand($this->dealerHand->showCards()),

            // 'dbg'   =>  $GLOBALS['dbg'],   //待完善，去掉所有dbg
        ));
    }
    /**
     * 输出json数据
     */
    public static function joutput($ret, $data = array()) {
        $out = array_merge(array('ret' => self::$ret[$ret]), $data);
        exit(json_encode($out));
    }
    /**
     * 展示手牌
     * @return array (扑克牌手牌图片地址， array(是否爆掉， 最高得分))
     */
    static public function showHand($cards) {
        $arr = array();
        foreach ($cards as $card) {
            array_push($arr, self::getImage($card));
        }
        return $arr;
    }
    /**
     * 是否必赢
     */
    private function bwin() {
        $rate = 30;  //{$rate}%
        $n = rand(1, 100);
        if ($n > $rate) {
            return false;
        }
        return true;
    }
    /**
     * 获取手牌对象
     */
    private function getHand($new = false) {
        if ($new) {
            $this->dealerHand = new BlackJackHand('dealer');
            $this->userHand = new BlackJackHand('user');
            $dealerHandScore = $this->dealerHand->getTopScore();
            $userHandScroe = $this->userHand->getTopScore();
            if ($this->bwin() && $dealerHandScore < $userHandScroe) {
                $handTmp = $this->dealerHand;
                $this->dealerHand = $this->userHand;
                $this->userHand = $handTmp;
            }
        } else {    //从存储中获取，可以是session
            $data = BlackSession::getStoreData();
            $this->dealerHand = unserialize($data['dh']);
            $this->userHand = unserialize($data['uh']);
        }
    }
    static private function getImage($card) {
        if($card) {
            $rank = $card[0];
            if($rank == BlackJackCards::JACK) {
                $rank = 'j';
            } elseif($rank == BlackJackCards::QUEEN) {
                $rank = 'q';
            } elseif($rank == BlackJackCards::KING) {
                $rank = 'k';
            }   
            $suit = substr($card[1], 0, 1);
            $image = 'images/'.$suit.$rank.'.png';
            return "<img src=\"$image\" />";
        }
    }
}
class BlackSession {
    static protected $is_init;
    static public function init() {
        if (! self::$is_init) {
            session_start();
            self::$is_init = true;
        }
    }
    static public function getStoreData() {
        self::init();
        return $_SESSION['blackjack'];
    }
    static public function storeData($data) {
        self::init();
        $_SESSION['blackjack'] = $data;
    }
}
BlackSession::init();   //不是session的可以考虑注释掉这一行
new BlackJack();
