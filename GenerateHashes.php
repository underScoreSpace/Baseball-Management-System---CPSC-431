<!-- USED TO FIND PASSWORDS IN THE TEST DATA (WILL REMOVE LATER) -->
<?php
echo 'Hash for "password":<br>';
echo password_hash('password', PASSWORD_DEFAULT);