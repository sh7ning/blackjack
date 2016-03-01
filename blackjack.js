document.charset="utf-8";
(function () {
    window.console && window.console.info("喜欢看代码，还想看php源代码？\n $_GET['code'] == 'show'");
    function preLoadImg (arr) {
        var newImgs = [], loadedImgs = 0;
        var call_back = function() {}  //此处增加了一个call_back函数
        var arr = (typeof arr != "object") ? [arr] : arr;
        function ImgLoadPost() {
            loadedImgs++;
            if (loadedImgs == arr.length) {
                call_back(newImgs); //加载完成用我们调用call_back函数并将newImgs数组做为参数传递进去
            }
        }
        for (var i = 0, arr_len = arr.length; i < arr_len; i++) {
            newImgs[i] = new Image();
            newImgs[i].src = arr[i];
            newImgs[i].onload = function() {
                ImgLoadPost();
            }
            newImgs[i].onerror = function(){
                ImgLoadPost();
            }
        }
        return { //此处返回一个空白对象的done方法
            'done' : function(f) {
                call_back = f || call_back;
            }
        }
    }
    var card_rank = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 'j', 'q', 'k'];
    var card_suit = ['s', 'h', 'c', 'd'];
    var card_imgs = [];
    for (var r = 0; r < 13; r++) {
        for (var s = 0; s < 4; s++) {
            card_imgs.push('images/' + card_suit[s] + card_rank[r] + '.png');
        }
    }
    window.console && window.console.log(card_imgs);
    preLoadImg(card_imgs).done(function () {
        // alert('加载完成图片');
    });
})();
$().ready(function() {
    function log(data) {
        window.console && window.console.log(data);
    }
    var call_back = function (obj) {
        log(obj);
        this.r = obj.r;
        this.action = obj.action;
    }
    call_back.prototype = {
        'run'   : function () {
            if (this.ret()) {
                this.data().ui();
            }
        },
        'ret'   : function () {
            var go = false;
            // log(this.r.ret);
            switch (this.r.ret) {
                case 0:
                    go = true;
                    break;
                case 1:
                    alert('错误的操作');
                    break;
                case 2:
                    alert('非法');
                    break;
                default:
                    log('未知的返回值');    //待完善，用于调试
                    break;
            }
            return go;
        },
        'ui'    : function () {
            switch (this.action) {
                case 'deal':
                    //展示初始牌，庄家一张牌亮，一张不亮， 用户两张同时亮
                    $('.deal_btn').addClass('vh');
                    $('.hit_btn, .stand_btn').removeClass('vh');
                    break;
                case 'hit':
                    //用户增加扑克牌，可能会爆掉
                    if (this.r.user[1][0]) {
                        $('.deal_btn').removeClass('vh');
                        $('.hit_btn, .stand_btn').addClass('vh');
                        alert('你输了，超过21点了，爆掉');
                    }
                    break;
                case 'stand':
                    $('.deal_btn').removeClass('vh');
                    $('.hit_btn, .stand_btn').addClass('vh');
                    switch (this.r.result) {
                        case 1:
                            alert('恭喜！你获胜了');
                            break;
                        case 2:
                            alert('哦豁，你输了，再接再厉');
                            break;
                        case 3:
                            alert('平局，再来一次');
                            break;
                        default:
                            log('win_def'); //待完善，要去掉
                            break;
                    }
                    break;
            }
            return this;
        },
        'data'  : function () {
            r = this.r;
            var d1 = $('#user_hand').html();
            var d2 = $('.hands h2 .user').text();
            var d3 = $('#dealer_hand').html();
            var d4 = $('.hands h2 .dealer').text();
            switch (this.action) {
                case 'deal':    //开始
                    d1 = '';
                    d3 = '';
                    break;
                case 'hit': //要牌
                    break;
                case 'stand':
                    d3 = '';
                    // log(r.dbg); //待完善，调试用
                    break;
                default:
                    log('未知的操作');    //待完善，调试用
                    break;
            }

            $('#user_hand').html( d1 + r.user[0].join('') );
            $('.hands h2 .user').text( r.user[1][1] );
            $('#dealer_hand').html( d3 + r.dealer[0].join('') );
            $('.hands h2 .dealer').text( r.dealer[1][1] );
            return this;
        }
    }
    $(".deal_btn").click(function() {   //开始
        var _this = this;
        $.post('', {'do' : 'deal'}, function(r) {
            var c = new call_back({'r' : r, 'action' : 'deal'});
            c.run();
        }, 'json');
    });
    $(".hit_btn").click(function() {    //要牌
        $.post('', {'do' : 'hit'}, function(r) {
            //用户增加扑克牌，可能会爆掉
            var c = new call_back({'r' : r, 'action' : 'hit'});
            c.run();
        }, 'json');
    });
    $(".stand_btn").click(function() {  //比牌
        $.post('', {'do' : 'stand'}, function(r) {
            //比大小，告诉结果
            var c = new call_back({'r' : r, 'action' : 'stand'});
            c.run();
        }, 'json');
    });
});
