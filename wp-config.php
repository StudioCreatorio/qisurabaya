<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}


define('AUTH_KEY',         'ECisfHLygNOw6WSEXaS3+eWUMWrBChqa+CzmkK8WYc0CxDLJw8fxbNcfHJaPx+wRptEzm7KC8qAF6H2SON0HeA==');
define('SECURE_AUTH_KEY',  'E9iXUHzvMQozb5zSGujpdb/zIB9AvXlAYoUX8NFYjvVodKvRh9FpJKBZbGFdOQYXLDaoaZKqlqhPysiMmEd8og==');
define('LOGGED_IN_KEY',    'oYT1zB419+xIAs1JSn5Oz/pdJCzBa2zaWb67FOt3ExOH3racYPLvOwFUWe/6yhTQcbVn1sXyUVVl3dzTt1zzEw==');
define('NONCE_KEY',        '5yvtrlodRC8biRt6P7jfn1NVDhiui0MGigxgG5EGqPW+5wFv1WzLIGKMSmWTdqVxBs8AQamkL9rI5DVwydmbtg==');
define('AUTH_SALT',        'oUlLOoHRp5MZAqvWzBAdmg9BdrcMuzEn3MP7Gs/2dcyKpI0jNT4T6aJcyv51sFyHGA8PR5poFc/WEpvNPWQmeQ==');
define('SECURE_AUTH_SALT', '3W+Fn2nZ72iXK+wqC/yZMgnJkWH9C7wWVnU+9oEuAnLfcS1+YBWV4f0azB8nlK9eGGL8pdkEcaIle1Ox6yVNlQ==');
define('LOGGED_IN_SALT',   '2nj1oWdB3CvIk0Rqumj8THoKjCmT3nQoIhnazjtmZTOEqrOzb0RWXEpXSsuihcqPvQ0TzSWzoc1aOfHPihkgkA==');
define('NONCE_SALT',       'ZhPoC8IaCXIOUc3W3N0RVNBhPSOgR37T51vvkoILbazu+M4LJpUMyDAc1wGsaox6jrZlIUhMl+6MqdgibtuSIw==');
define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
