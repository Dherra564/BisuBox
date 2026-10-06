USE bdbisubox;

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO
    tbusuario (
        tbusuarioid,
        tbusuarioidentificaciontipo,
        tbusuarioidentificacionnumero,
        tbusuarionombrecompleto,
        tbusuarioperfilimagen,
        tbusuariocorreo,
        tbusuariotelefono,
        tbusuariocontrasena,
        tbusuarioregistrofecha,
        tbusuarioactivo
    )
SELECT 1, 'Cedula', '100000001', 'Administrador General', NULL, 'admin@bisubox.com', '88888888', '$2y$12$sNrx5WFwUFGk.6EZAWlEeupmQr6jtzbEHnDtvetlS98knq7HpxoYi', NOW(), 1
FROM DUAL
WHERE
    NOT EXISTS (
        SELECT 1
        FROM tbusuario
        WHERE
            tbusuarioid = 1
            OR tbusuariocorreo = 'admin@bisubox.com'
    );

INSERT INTO
    tbsuperadmin (
        tbsuperadminid,
        tbusuarioid,
        tbsuperadminactivo
    )
SELECT 1, 1, 1
FROM DUAL
WHERE
    NOT EXISTS (
        SELECT 1
        FROM tbsuperadmin
        WHERE
            tbsuperadminid = 1
            OR tbusuarioid = 1
    );

COMMIT;