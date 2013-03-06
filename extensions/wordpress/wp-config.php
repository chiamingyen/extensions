<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'database_name_here');

/** MySQL database username */
define('DB_USER', 'username_here');

/** MySQL database password */
define('DB_PASSWORD', 'password_here');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
define('DB_TYPE', 'sqlite');	// use sqlite

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '_j/tgh31Tl++PM+]K|IW- .sm4/FX. 5.K&+*W>6qMP+e|nm_J}HlM+3p_*%JTED');
define('SECURE_AUTH_KEY',  'Y;SR:7+4@~1SQtHVO0<Nev/Q4.FMl>M-24zxzCVzx<csd;GZe/(C&J_5WzG$ca~=');
define('LOGGED_IN_KEY',    'KSy%q~}M.O1c]WzS#+U-^E`V<?kQbwm[$Eo|zOM:oIxN13L09-g=ZM]#dH-!B+s{');
define('NONCE_KEY',        'D=Y;km<X^bKYpQQ#&0!OJ:vv*KkD#052m`-Qya2?H[M?@&2sTF4)0sL5tfg|RZ0W');
define('AUTH_SALT',        'xovWl+I[t/Oi] g,p5Uc]6~+o:KgLfy#?l%IFc/{aD@]WPF}N#(Vv-A,RD8`}&ji');
define('SECURE_AUTH_SALT', 'o40+h3:Dc#t2)D-8dx}zy*8X/FJi,Zr|BJ3~ -5!&7.A%[g/s<`?Q{emb22+>m%#');
define('LOGGED_IN_SALT',   '2#-N[6(DO$++/~kva#h?(Xv/t],;V+p%.Q~}|Br#_AG&DJ0HI:b`}nQZb:Tq7F0U');
define('NONCE_SALT',       'YO-F^%8XF0EH/iWo8GxOsGH,b2lP27S(>+g9xwTb@g)5AGg2|a!+B<F+oiWL3@f<');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
