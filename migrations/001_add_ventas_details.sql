-- Migration: Add payment and shipping columns to ventas
ALTER TABLE ventas
  ADD COLUMN impuesto DECIMAL(10,2) DEFAULT 0 AFTER subtotal,
  ADD COLUMN metodo_pago VARCHAR(50) NULL AFTER impuesto,
  ADD COLUMN direccion_envio TEXT NULL AFTER metodo_pago,
  ADD COLUMN telefono VARCHAR(50) NULL AFTER direccion_envio;

-- Optional: index for buscar por metodo_pago
CREATE INDEX IF NOT EXISTS idx_metodo_pago ON ventas (metodo_pago);
