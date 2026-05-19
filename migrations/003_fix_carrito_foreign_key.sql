-- Migración 003: Remover restricción de foreign key en carrito
-- Razón: La tabla carrito ahora almacena tanto productos (id < 10000) como recargas (id >= 10000)
-- La restricción previa solo permitía productos, causando error al insertar recargas

ALTER TABLE carrito DROP FOREIGN KEY carrito_ibfk_2;

-- Nota: No re-agregamos la restricción porque:
-- 1. El carrito es una tabla de sesión temporal
-- 2. Los datos ya están validados en la aplicación (procesar_compra.php)
-- 3. La restricción impediría almacenar recargas con prefijo 10000+
-- 4. Las integridades se validan a nivel de aplicación antes de procesar
