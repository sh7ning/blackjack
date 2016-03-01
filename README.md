# blackjack

使用PHP, JQuery, Html 和 CSS实现的21点游戏。

> 本版本为心机版，可以用于项目使用

![效果图](https://raw.githubusercontent.com/sh7ning/img/master/blackJack.gif "效果图")

### 一些说明

* 为了防止bug出现，可能需要进行一些必要的处理

	1. 为了防止一个人打开多个页面，然后出现混乱的抓牌结果，页面打开后，可以做个单页处理(可以采用falsh实现，使用LocalConnection)，当然也可以采用每个页面独立的一个key（存储不采用session更换为memcache或则redis，看代码，写的比较方便更改的，对，就改那个session类即可，key跟uid+页面每次打开随机生成数字有关即可一个人打开多个网页分别玩耍）

	1. 页面打开后，要监听页面关闭事件，告诉用户关闭了将直接结束（结果为失败）