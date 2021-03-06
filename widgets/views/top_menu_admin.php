<?php

use yii\helpers\Url;

?>

<ul>
    <li class="has-dropdown">
        <a href="<?=Url::toRoute(['news/my-news'])?>">Мои Статьи</a>
        <ul class="dropdown">
            <li><a href="<?=Url::toRoute(['news/form'])?>">Написать</a></li>
        </ul>
    </li>
    <li class="has-dropdown">
        <a href="<?=Url::toRoute(['user/index'])?>">Пользователи</a>
        <ul class="dropdown">
            <li><a href="<?=Url::toRoute(['user/send-notify'])?>">Отправить уведомление</a></li>
        </ul>
    </li>
    <li class="has-dropdown">
        <a href="#"><?= Yii::$app->user->GetUserName() ?></a>
        <ul class="dropdown">
            <li><a href="<?=Url::toRoute(['user/profile'])?>">Профиль</a></li>
            <li><a href="<?=Url::toRoute(['user/logout'])?>">Выйти</a></li>
        </ul>
    </li>
</ul>