<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
?>
    </main>

    <footer style="
        background: rgba(10, 10, 10, 0.95);
        border-top: 3px solid #6200ff;
        color: #e0e0e0;
        padding: 40px 0 20px;
        margin-top: 60px;
    ">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 style="color: #00d4ff; margin-bottom: 15px;">
                        <i class="fas fa-gamepad"></i> Sobre Nosotros
                    </h5>
                    <p style="font-size: 0.95rem; color: #aaa;">
                        Somos la tienda online especializada en videojuegos, hardware gamer y artículos frikis más grande del país.
                    </p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 style="color: #00d4ff; margin-bottom: 15px;">
                        <i class="fas fa-phone"></i> Contacto
                    </h5>
                    <p style="font-size: 0.95rem;">
                        <strong><?php echo htmlspecialchars(TELEFONO_TIENDA); ?></strong>
                        <br>
                        <strong><?php echo htmlspecialchars(EMAIL_TIENDA); ?></strong>
                    </p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 style="color: #00d4ff; margin-bottom: 15px;">
                        <i class="fas fa-link"></i> Enlaces Rápidos
                    </h5>
                    <ul style="list-style: none; padding: 0; font-size: 0.95rem;">
                        <li style="margin-bottom: 8px;">
                            <a href="<?php echo BASE_URL; ?>client/catalogo.php" style="color: #e0e0e0; text-decoration: none;">
                                Catálogo
                            </a>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <a href="<?php echo BASE_URL; ?>client/historia.php" style="color: #e0e0e0; text-decoration: none;">
                                Historia
                            </a>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <a href="<?php echo BASE_URL; ?>client/contacto.php" style="color: #e0e0e0; text-decoration: none;">
                                Contacto
                            </a>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <a href="<?php echo BASE_URL; ?>auth/login.php" style="color: #e0e0e0; text-decoration: none;">
                                Mi Cuenta
                            </a>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <a href="<?php echo BASE_URL; ?>client/carrito.php" style="color: #e0e0e0; text-decoration: none;">
                                Carrito
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: #333; margin: 30px 0;">
            <div style="text-align: center; color: #888; font-size: 0.9rem;">
                <p style="margin: 0;">
                    &copy; afv Gamer Friki. Todos los derechos reservados.
                    <br>
                    <small>Diseño épico para gamers y frikis</small>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
