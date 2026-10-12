<?php

test('トップページが表示される', function () {
    $this->get('/')->assertOk();
});
