-- Quita el módulo "Ingreso diarios" (registroCierre.php y admCierre.php):
-- las aperturas y cierres de caja (cierres_caja.php) ya cubren esa función.
-- La tabla `cierres` se conserva con sus datos (solo deja de usarse).

DELETE FROM users_permisos
WHERE id_item_menu IN (SELECT id FROM menu WHERE dir = 'registroCierre.php');

DELETE FROM menu WHERE dir = 'registroCierre.php';
