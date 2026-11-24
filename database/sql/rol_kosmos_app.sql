-- Ejecutar UNA vez como superusuario, DESPUÉS de `php artisan migrate`.
-- No va en migraciones: crea un rol con contraseña y requiere permisos de superusuario.

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'kosmos_app') THEN
        CREATE ROLE kosmos_app LOGIN PASSWORD 'cambiar_esta_clave';
    END IF;
END;
$$;

GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO kosmos_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO kosmos_app;
REVOKE UPDATE, DELETE, TRUNCATE ON movimientos, auditoria, legalizaciones, legalizacion_detalles, ajustes FROM kosmos_app;
