<?php

/*
 * Copyright (C) 2006-2018 Kerno CMS
 *
 * Name: AuthBase.php
 * Description: registration plugin
 *
 * @author Vitaly Ponomarev
 * @author Dmitry Ryzhkov
*/

interface AuthProvider {

    // при успешном входе возвращает массив с данными пользовался
    public function login($username, $password);

    // Зарегистрировать пользователя
    public function register(&$params, $values, &$msg);

    // сохранить в БД информацию о том, что пользователь авторизовался
    public function saveAuth($userRow);

    // проверить авторизацию пользователя
    public function checkAuth();

    // снять авторизацию
    public function dropAuth();

    // возвращает пароль (хэш)
    public function createPassword($password);

    // возвращает булевый результат верификации пароля
    public function verifyPassword($password, $hash);

    public function validateLogin(&$msg, $login);

    public function validatePassword(&$msg, $password, $password2 = null, $checkEqual = true);

    public function validateEmail(&$msg, $email);

    public function getRegParams();

    public function getRestorePasswordParams();

    public function restorePassword(&$params, $values, &$msg);

    public function checkResetCode(&$msg, $resetCode);

    public function setNewPasswordAfterReset(&$msg, $resetCode, $password);

    // AJAX call - online check registration parameters for correct valuescheck if login is available
    // Input:
    // $params - array of 'fieldName' => 'fieldValue' for checking
    // Returns:
    // $result - array of 'fieldName' => status
    // List of statuses:
    // 0	- Method not implemented [ this field is not checked/can't be checked/... ] OR NOT SET
    // 1	- Occupied
    // 2	- Incorrect length
    // 3	- Incorrect format
    // 100	- Available for registration
    public function onlineCheckRegistration($params);
}