<?php
function (
    string $date,
    string $name,
    string $sitename,
    string $type
) {
?>

    <?= $name ?> 您好，

    感谢您的付款！您在<?= $sitename ?>网站的<?= $type ?>的有效日期已经延长至<?= $date ?>。
    如有问题或者广告内容需要修改，可以随时联系我们。

    Best,
    <?= $sitename ?> Ads

<?php
};
