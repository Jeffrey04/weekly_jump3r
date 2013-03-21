<?php

function request_get_step() {
    return request_has_multiple() ?
        ((request_has_count() OR $_GET['multiple'] == 0) ?
            2 : 1) :
        0;
}

function request_has_count() {
    return array_key_exists('count', $_GET) AND is_numeric($_GET['count']);
}

function request_has_multiple() {
    return array_key_exists('multiple', $_GET) AND is_numeric($_GET['multiple']);
}

function medal_get_url($badge_id, $count) {
    return sprintf(
        'http://%s%s/medal.php?badge_id=%s%s',
        $_SERVER['SERVER_NAME'],
        dirname($_SERVER['PHP_SELF']),
        $badge_id,
        empty($count) ?
            NULL :
            "&count={$count}"
    );
}

function badge_query_multiple(Array $database, $multiple) {
    return array_filter(
        $database,
        function($badge) use($multiple) {
            return $badge['multiple'] == $multiple;
        }
    );
}

function database() {
    return json_decode(file_get_contents('database.json'), TRUE);
}

function database_multiple() {
    return array(
        array(
            'name' => '每週徽章',
            'multiple' => 1,
            'description' => '以一週為單位的徽章',
            'instruction' => '輸入成功達成目標的週數'
        ),
        array(
            'name' => '每月徽章',
            'multiple' => 4,
            'description' => '以一月或四週為單位的徽章',
            'instruction' => '輸入成功達成目標的月數，注意一個月等於四週'
        ),
        array(
            'name' => '十篇徽章',
            'multiple' => 10,
            'description' => '以每十篇為單位的徽章',
            'instruction' => '輸入文章總數除以十的倍數，略掉餘數'
        ),
        array(
            'name' => '其他徽章',
            'multiple' => 0,
            'description' => '其他活動相關徽章'
        )
    );
}
?>
<!DOCTYPE html>
<html>
<head>
<title>跳坑勇士徽章生成頁</title>
<meta charset="utf-8" />
</head>
<body>

    <h1>徽章配對程序</h1>

    <?php if(request_get_step() == 2) : // show result ?>
    <?php $badge_list = badge_query_multiple(database(), $_GET['multiple']); ?>

    <?php if(count($badge_list) > 0) : ?>
    <h2>所獲徽章</h2>

    <dl>
    <?php foreach($badge_list as $badge_id => $badge_type) : ?>
        <?php if(array_key_exists('tiers', $badge_type) AND is_array($badge_type['tiers'])) : ?>
            <dt><?php echo $badge_type['name']; ?></dt>
            <dd><?php echo $badge_type['description']; ?></dd>
            <dd><dl>
            <?php foreach($badge_type['tiers'] as $badge) : ?>
                <dt>第<?php echo $badge['level']; ?>級</dt>
                <?php if($_GET['count'] >= $badge['min_count']) : ?>
                <dd>徽章：<img alt="<?php echo $badge_type['name']; ?>" src="<?php echo medal_get_url($badge_id, $badge['min_count']); ?>" width="100" height="20" /></dd>
                <?php if(array_key_exists('min_week', $badge_type)) : ?>
                <dd>
                    條件：連續
                    <?php echo $badge['min_count']; ?>
                    <?php if($badge_type['multiple'] == 1) : ?>週<?php else : ?>月<?php endif; ?>每週至少發佈
                    <?php echo $badge_type['min_week']; ?>
                    篇，總計不少於
                    (<?php echo $badge['min_count']; ?> * <?php echo $badge_type['min_week']; ?> = <?php echo $badge['min_count'] * $badge_type['min_week']; ?>)
                    篇文章。
                </dd>
                <?php elseif($badge_type['multiple'] == 10) : ?>
                <dd>
                    條件：總共發佈至少
                    （<?php echo $badge_type['multiple']; ?> * <?php echo $badge['min_count']; ?> = <?php echo $badge_type['multiple'] * $badge['min_count']; ?>）
                    篇。
                </dd>
                <?php endif; ?>
                <dd>代碼：<textarea cols="75" rows="5">&lt;!--<?php echo $badge['name']; ?>第<?php echo $badge['level']; ?>級開始--&gt;
&lt;a href="http://feeds.feedburner.com/WeeklyJump3r"&gt;&lt;img alt="<?php echo $badge_type['name']; ?>第<?php echo $badge['level']; ?>級" src="<?php echo medal_get_url($badge_id, $badge['min_count']); ?>" width="100" height="20" /&gt;&lt;/a&gt;
&lt;!--跳坑勳章結束--&gt;</textarea></dd>
                <?php else : ?>
                <dd>有待揭曉</dd>
                <?php endif; ?>
            <?php endforeach; ?>
            </dl></dd>
        <?php elseif($badge_id != 0) : ?>
            <dt><?php echo $badge_type['name']; ?></dt>
            <dd><?php echo $badge_type['description']; ?></dd>
            <dd>徽章：<img alt="<?php echo $badge_type['name']; ?>" src="<?php echo medal_get_url($badge_id, NULL); ?>" width="100" height="20" /></dd>
                <dd>代碼：<textarea cols="75" rows="5">&lt;!--<?php echo $badge['name']; ?>開始--&gt;
&lt;a href="http://feeds.feedburner.com/WeeklyJump3r"&gt;&lt;img alt="<?php echo $badge_type['name']; ?>" src="<?php echo medal_get_url($badge_id, NULL); ?>" width="100" height="20" /&gt;&lt;/a&gt;
&lt;!--跳坑勳章結束--&gt;</textarea></dd>
        <?php endif; ?>
    <?php endforeach; ?>
    </dl>

    <h3>備註：</h3>
    <ol>
    <li><strong>可升級</strong>意即如果滿足了下一級的條件，便可於網誌加貼下一級別的徽章，也就是說同一徽章有好幾個不同的級別可同時貼上。</li>
    <li>揪跳人表示無法控制大家作弊亂貼徽章，只能假設大家都很自律。</li>
    <li>活動從貳零壹叄年壹月拾叄日始開跑，所以第一次的四周將在貳零壹叄年貳月玖日截止（請參考以下日期列表），以此類推。</li>
    </ol>

    <?php else: ?>
    <h2>參數錯誤</h2>
    <p>參數出現錯誤，請<a href="<?php echo "http://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}"; ?>">回首頁</a>重新選擇。</p>
    <?php endif; ?>

    <?php elseif(request_get_step() == 1) : // get count parameter ?>

    <?php
        $type = array_pop(badge_query_multiple(database_multiple(), $_GET['multiple']));

        if(empty($type) !== FALSE) : ?>

    <h2>參數錯誤</h2>
    <p>參數出現錯誤，請<a href="<?php echo "http://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}"; ?>">回首頁</a>重新選擇。</p>

    <?php else : ?>

    <h2>參數輸入</h2>
    <form method="get" action="<?php echo "http://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}"; ?>">
        <label for="count"><?php echo $type['instruction']; ?></label>
        <input name="count" />
        <input type="hidden" name="multiple" value="<?php echo $type['multiple']; ?>" />
        <input type="submit" />
        <p>備註：每四週為一月，相關日期請查詢以下時間表</p>
    </form>

    <h3>相關結帳日</h3>

    <p>活動從2013/1/13開跑，一直到部落格祭前一週的星期六。每週從星期天開始，然後星期六結束。每周小結一次，四周（每月）大結一次（下列表粗體為每月最後一天）。</p>

    <p>擺德威，揪跳人不知道部落格祭確實日期，但是這活動暫定跑40週</p>

    <ul>
    <?php for($date = date_create('2013-01-13'), $i = 1;
            $i <= 40; $i++, $date->modify('+1 day')) : ?>
        <li>第<?php echo $i; ?>週：
        <?php if($i % 4 == 0): ?><strong><?php endif; ?>
        <?php echo $date->format('Y/m/d'); ?> 起
        <?php echo $date->modify('+6 days')->format('Y/m/d'); ?> 止
        <?php if($i % 4 == 0): ?>（第<?php echo $i / 4; ?>個月/四周截止）</strong><?php endif; ?>
        </li>
    <?php endfor; ?>
    </ul>

    <?php endif; ?>

    <?php else : ?>

    <h2>跳坑勇士徽章說明頁</h2>

    <p>還不知道會不會有時間繼續弄下去，不過徽章生成器目前堪稱能用。</p>

    <p>暫時一切採取最簡便的操作方式，站點以後有時間再生出來好了，所以徽章如果覺得自己合格就自己掛上網吧。是不是合格你自己最清楚，嘿嘿。</p>

    <p>揪跳人 - <a href="http://jeff.coolsilon.com/">傑夫</a></p>

    <p>
        跳坑活動相關網站：
        <a href="http://jeff.coolsilon.com/2013/01/03/1696/">揪跳文</a> |
        <a href="http://www.facebook.com/WeeklyJump3r">臉書</a> |
        <a href="http://www.twitter.com/WeeklyJump3r">推特</a> |
        <a href="http://feeds.feedburner.com/WeeklyJump3r">聯播</a></p>
    </p>

    <h2>選擇徽章類型</h2>
    <dl>
    <?php foreach(database_multiple() as $badge) : ?>
    <dt><a href="<?php echo "http://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}?multiple={$badge['multiple']}" ?>"><?php echo $badge['name']; ?></a></dt>
    <dd><?php echo $badge['description']; ?></dd>
    <?php endforeach; ?>
    </dl>
    <?php endif; ?>
</body>
</html>
