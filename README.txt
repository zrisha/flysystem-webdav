Flysystem WebDAV
=================

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:


'webdav' => [
  'driver' => 'webdav',
  'config' => [
    'base_uri' => 'http://my-webdav-repo.com',
    'user_name' => 'username',
    'password' => 'password,
    'prefix' => 'webdav/index.php',
    'path' => 'documents'
  ],
  'cache' => TRUE,
]
