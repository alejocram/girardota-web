</main>

<footer class="gt-footer">
	<div class="gt-container">
		<div class="gt-footer__grid">
			<div>
				<a class="gt-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo gt_logo_svg(); // phpcs:ignore ?>
					<span><span class="gt-logo__name">Girardota<span>.com.co</span></span><span class="gt-logo__tag">Turismo de naturaleza · Antioquia</span></span>
				</a>
				<p>Glamping, tours de café, aventura y sabores locales en Girardota, a poco más de media hora de Medellín. Cada reserva apoya a las familias, guías y productores del municipio.</p>
				<div class="gt-footer__social">
					<a href="<?php echo esc_url( gt_social()['instagram'] ); ?>" target="_blank" rel="noopener" aria-label="Instagram"><?php echo gt_icon( 'instagram' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( gt_social()['facebook'] ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo gt_icon( 'facebook' ); // phpcs:ignore ?></a>
				</div>
			</div>
			<div>
				<h4>Reserva</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Alojamientos</a></li>
					<li><a href="<?php echo esc_url( home_url( '/tours/' ) ); ?>">Tours y experiencias</a></li>
					<li><a href="<?php echo esc_url( home_url( '/reserva/domo-estrellas/' ) ); ?>">Glamping Domo Estrellas</a></li>
					<li><a href="<?php echo esc_url( home_url( '/reserva/ruta-del-cafe/' ) ); ?>">Ruta del Café</a></li>
					<li><a href="<?php echo esc_url( home_url( '/politicas-de-reserva/' ) ); ?>">Políticas de reserva</a></li>
				</ul>
			</div>
			<div>
				<h4>Descubre</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>">Restaurantes y cafés</a></li>
					<li><a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Agenda de eventos</a></li>
					<li><a href="<?php echo esc_url( home_url( '/girardota/' ) ); ?>">Conoce Girardota</a></li>
					<li><a href="<?php echo esc_url( home_url( '/noticias/' ) ); ?>">Noticias y guías</a></li>
					<li><a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">Nosotros</a></li>
				</ul>
			</div>
			<div>
				<h4>Contacto</h4>
				<ul>
					<li>WhatsApp: <a href="<?php echo esc_url( gt_wa() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( gt_wa_display() ); ?></a></li>
					<?php $gt_mail = function_exists( 'gt_contact_email' ) ? gt_contact_email() : 'info@girardota.com.co'; ?>
					<li>Correo: <a href="mailto:<?php echo esc_attr( $gt_mail ); ?>"><?php echo esc_html( $gt_mail ); ?></a></li>
					<li>Girardota, Antioquia, Colombia</li>
					<li><a href="<?php echo esc_url( home_url( '/nosotros/#contacto' ) ); ?>">Escríbenos</a></li>
				</ul>
				<p style="margin-top:1rem;font-size:.88rem">¿Tienes un alojamiento, tour o restaurante? <a href="<?php echo esc_url( home_url( '/nosotros/#contacto' ) ); ?>">Súmate como aliado →</a></p>
			</div>
		</div>
		<div class="gt-footer__bottom">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Girardota.com.co · Hecho con cariño en Girardota, Antioquia.</span>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'legal',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</div>
	</div>
</footer>

<a class="gt-wa-float" href="<?php echo esc_url( gt_wa( 'Hola, quiero hacer una reserva en Girardota.com.co' ) ); ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"></a>

<?php wp_footer(); ?>
</body>
</html>
