<?php
// ============================
// Validation Functions
// ============================

function validateFirstName($firstName) {
    return preg_match("/^[a-zA-Z]+$/", $firstName);
}

function validateLastName($lastName) {
    return preg_match("/^[a-zA-Z]+$/", $lastName);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}
?>