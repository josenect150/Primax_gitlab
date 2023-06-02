# Dist

Dist tendrá como función proporcionar una estructura ordenada de lado de front para los **Proyectos de Drupal**, haciendo uso y manejo de sass, js y recursos que el proyecto necesite.

Para la regla de importaciones en los estilos se recomienda el uso de la regla [@use](https://sass-lang.com/documentation/at-rules/use) en vez de [@import](https://sass-lang.com/documentation/at-rules/import) como lo comenta la documentación.

## Requisitos

- Tener instalado globalmente sass.

## Scripts

Instala sass globalmente
```
npm i -g sass
```

Compila los archivos sass. Debe ubicarse en la carpeta `dist/`
```
sass --watch scss/main.scss:css/main.css --style=compressed
```