<?php
session_start();
require '../config/db.php';
?>
<?php include '../includes/header.php'; ?>

<style>
    .historia-hero {
        background: linear-gradient(135deg, rgba(98, 0, 255, 0.15) 0%, rgba(0, 212, 255, 0.1) 100%);
        border: 2px solid rgba(0, 212, 255, 0.3);
        border-radius: 20px;
        padding: 60px 40px;
        margin-bottom: 50px;
        text-align: center;
        box-shadow: 0 0 30px rgba(98, 0, 255, 0.2);
    }

    .historia-hero h1 {
        color: #00d4ff;
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 20px;
        text-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
    }

    .historia-hero p {
        color: #cbd5e1;
        font-size: 1.1rem;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .timeline {
        position: relative;
        padding: 40px 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 3px;
        height: 100%;
        background: linear-gradient(180deg, #6200ff 0%, #00d4ff 50%, transparent 100%);
    }

    @media (max-width: 768px) {
        .timeline::before {
            left: 20px;
        }
    }

    .timeline-item {
        margin-bottom: 50px;
        position: relative;
    }

    .timeline-item:nth-child(odd) .timeline-content {
        margin-left: 0;
        margin-right: 52%;
        text-align: right;
    }

    .timeline-item:nth-child(even) .timeline-content {
        margin-left: 52%;
        margin-right: 0;
        text-align: left;
    }

    .timeline-dot {
        position: absolute;
        left: 50%;
        top: 0;
        transform: translateX(-50%);
        width: 20px;
        height: 20px;
        background: #00d4ff;
        border: 3px solid #0a0a0a;
        border-radius: 50%;
        box-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
        z-index: 1;
    }

    @media (max-width: 768px) {
        .timeline-item:nth-child(odd) .timeline-content,
        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 60px;
            margin-right: 0;
            text-align: left;
        }

        .timeline-dot {
            left: 20px;
        }
    }

    .timeline-content {
        background: rgba(26, 26, 46, 0.95);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 12px;
        padding: 25px;
        transition: all 0.3s ease;
    }

    .timeline-content:hover {
        border-color: #00d4ff;
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.15);
        transform: translateY(-3px);
    }

    .timeline-year {
        color: #00d4ff;
        font-size: 1.3rem;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .timeline-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    .timeline-text {
        color: #cbd5e1;
        line-height: 1.6;
    }

    .values-card {
        background: rgba(18, 18, 42, 0.96);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 16px;
        padding: 30px;
        height: 100%;
        transition: all 0.3s ease;
    }

    .values-card:hover {
        border-color: #00d4ff;
        box-shadow: 0 0 25px rgba(0, 212, 255, 0.2);
        transform: translateY(-8px);
    }

    .values-card-icon {
        font-size: 2.5rem;
        color: #00d4ff;
        margin-bottom: 15px;
        display: block;
    }

    .values-card h4 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .values-card p {
        color: #cbd5e1;
        line-height: 1.6;
        margin: 0;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 40px 0;
    }

    .stat-box {
        background: rgba(26, 26, 46, 0.95);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        border-color: #00d4ff;
        transform: translateY(-5px);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.15);
    }

    .stat-number {
        font-size: 2.5rem;
        color: #00d4ff;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .stat-label {
        color: #cbd5e1;
        font-weight: 600;
    }

    .cta-section {
        background: linear-gradient(135deg, rgba(98, 0, 255, 0.15) 0%, rgba(0, 212, 255, 0.1) 100%);
        border: 2px solid rgba(0, 212, 255, 0.3);
        border-radius: 20px;
        padding: 50px;
        text-align: center;
        margin-top: 50px;
    }

    .cta-section h2 {
        color: #00d4ff;
        font-size: 2.2rem;
        font-weight: 900;
        margin-bottom: 20px;
    }

    .cta-section p {
        color: #cbd5e1;
        font-size: 1.1rem;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-cta {
        display: inline-block;
        background: linear-gradient(135deg, #6200ff 0%, #8a2be2 100%);
        border: 2px solid #00d4ff;
        color: #fff;
        padding: 14px 40px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        margin: 0 10px;
        transition: all 0.3s ease;
    }

    .btn-cta:hover {
        background: linear-gradient(135deg, #8a2be2 0%, #6200ff 100%);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
        transform: translateY(-3px);
        color: #fff;
    }

    .mission-section {
        background: rgba(26, 26, 46, 0.95);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 40px;
    }

    .mission-section h3 {
        color: #fff;
        font-weight: 900;
        font-size: 1.8rem;
        margin-bottom: 20px;
    }

    .mission-section p {
        color: #cbd5e1;
        font-size: 1.05rem;
        line-height: 1.8;
        margin: 0;
    }
</style>

<!-- Hero Section -->
<div class="historia-hero">
    <h1><i class="fas fa-history"></i> Nuestra Historia</h1>
    <p>Descubre cómo Gamer Friki se convirtió en la tienda favorita de gamers y coleccionistas de toda la región.</p>
</div>

<!-- Estadísticas -->
<div class="stats-container">
    <div class="stat-box">
        <div class="stat-number">5+</div>
        <div class="stat-label">Años de Trayectoria</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">10K+</div>
        <div class="stat-label">Clientes Satisfechos</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">1K+</div>
        <div class="stat-label">Productos Disponibles</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">24/7</div>
        <div class="stat-label">Atención al Cliente</div>
    </div>
</div>

<!-- Línea de Tiempo -->
<div class="row my-5">
    <div class="col-12">
        <h2 style="color: #00d4ff; text-align: center; margin-bottom: 40px; font-size: 2rem; font-weight: 900;">
            <i class="fas fa-timeline me-2"></i>Nuestro Camino
        </h2>

        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2019</div>
                    <div class="timeline-title">El Inicio de una Pasión</div>
                    <div class="timeline-text">
                        Un grupo de gamers decidió crear un espacio donde compartir su pasión. Comenzamos vendiendo productos desde una pequeña tienda local en Cochabamba.
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2020</div>
                    <div class="timeline-title">Llegada al E-commerce</div>
                    <div class="timeline-text">
                        En plena pandemia, decidimos dar el salto digital. Lanzamos nuestra primera tienda online para llegar a más gamers en toda Bolivia.
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2021</div>
                    <div class="timeline-title">Expansión de Categorías</div>
                    <div class="timeline-text">
                        Ampliamos nuestro catálogo incluyendo hardware, accesorios, coleccionables, mangas y recargas digitales. Ahora ofrecemos todo lo que un gamer necesita.
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2023</div>
                    <div class="timeline-title">Rediseño Moderno</div>
                    <div class="timeline-text">
                        Renovamos completamente nuestra plataforma con un diseño oscuro, moderno y más amigable. Mejoramos la experiencia de compra significativamente.
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2024-2025</div>
                    <div class="timeline-title">Hoy: Tu Tienda Favorita</div>
                    <div class="timeline-text">
                        Somos la tienda de referencia para gamers en Cochabamba y Bolivia. Seguimos creciendo, innovando y mejorando cada día para ti.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Misión y Visión -->
<div class="row gy-4 mb-5">
    <div class="col-lg-6">
        <div class="mission-section">
            <h3><i class="fas fa-bullseye me-2"></i>Nuestra Misión</h3>
            <p>
                Ser el principal proveedor de productos gamers, tecnología y cultura pop en América Latina, ofreciendo calidad, variedad y excelente servicio al cliente. Queremos que cada gamer encuentre en Gamer Friki todo lo que necesita para mejorar su experiencia, desde lo último en videojuegos hasta accesorios profesionales y artículos de colección únicos.
            </p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mission-section">
            <h3><i class="fas fa-rocket me-2"></i>Nuestra Visión</h3>
            <p>
                Convertirnos en la marca más confiable y reconocida en el mundo gamer. Aspiramos a crear una comunidad vibrante donde gamers de todas las edades y niveles encuentren inspiración, productos exclusivos y una experiencia de compra memorable que vaya más allá de una simple transacción.
            </p>
        </div>
    </div>
</div>

<!-- Valores -->
<div class="row gy-4 mb-5">
    <h2 style="color: #00d4ff; text-align: center; margin-bottom: 30px; font-size: 2rem; font-weight: 900;">
        <i class="fas fa-heart me-2"></i>Nuestros Valores
    </h2>

    <div class="col-lg-3 col-md-6">
        <div class="values-card">
            <i class="fas fa-fire values-card-icon"></i>
            <h4>Pasión</h4>
            <p>Cada miembro del equipo ama los videojuegos y la cultura gamer. Esta pasión se refleja en cada producto que ofrecemos.</p>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="values-card">
            <i class="fas fa-handshake values-card-icon"></i>
            <h4>Confianza</h4>
            <p>Transparencia en nuestras operaciones, productos auténticos garantizados y compromiso con cada cliente.</p>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="values-card">
            <i class="fas fa-star values-card-icon"></i>
            <h4>Calidad</h4>
            <p>Solo los mejores productos llegan a nuestro catálogo. Cuidamos cada detalle para garantizar satisfacción.</p>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="values-card">
            <i class="fas fa-users values-card-icon"></i>
            <h4>Comunidad</h4>
            <p>Más que vender, creamos espacios donde la comunidad gamer se encuentra, comparte y crece juntos.</p>
        </div>
    </div>
</div>

<!-- Sección "Más que una tienda" -->
<div class="row mb-5">
    <div class="col-12">
        <div class="mission-section">
            <h3 style="color: #00d4ff; font-size: 1.8rem;">
                <i class="fas fa-lightbulb me-2"></i>Más que una Tienda
            </h3>
            <p style="margin-bottom: 20px;">
                En Gamer Friki no solo vendemos productos: somos un espacio para la comunidad, un lugar donde gamers, coleccionistas y amantes de la cultura pop pueden:
            </p>
            <ul style="color: #cbd5e1; line-height: 2; font-size: 1.05rem;">
                <li><i class="fas fa-check me-2" style="color: #00d4ff;"></i>Descubrir las últimas novedades en videojuegos y tecnología</li>
                <li><i class="fas fa-check me-2" style="color: #00d4ff;"></i>Encontrar accesorios y periféricos de calidad para mejorar su setup</li>
                <li><i class="fas fa-check me-2" style="color: #00d4ff;"></i>Coleccionar figuras, Funkos y artículos únicos de sus franquicias favoritas</li>
                <li><i class="fas fa-check me-2" style="color: #00d4ff;"></i>Acceder a recargas digitales y contenido exclusivo</li>
                <li><i class="fas fa-check me-2" style="color: #00d4ff;"></i>Conectar con otros gamers y compartir experiencias</li>
            </ul>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <h2>¿Listo para tu próxima aventura?</h2>
    <p>Explora nuestro catálogo completo de productos gamers, o contáctanos si tienes preguntas o sugerencias.</p>
    <a href="<?php echo BASE_URL; ?>client/catalogo.php" class="btn-cta">
        <i class="fas fa-shopping-bag me-2"></i>Explorar Catálogo
    </a>
    <a href="<?php echo BASE_URL; ?>client/contacto.php" class="btn-cta">
        <i class="fas fa-envelope me-2"></i>Contactarnos
    </a>
</div>

<?php include '../includes/footer.php'; ?>

