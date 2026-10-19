# Consulta de paraderos SITP por localidad de Bogotá

## Descripción del proyecto

Este proyecto permite consultar información de los paraderos zonales del Sistema Integrado de Transporte Público (SITP) de Bogotá, seleccionando una localidad mediante un formulario web desarrollado en PHP.

## Endpoint utilizado

Se utilizó el servicio REST de ArcGIS de Catastro Bogotá:

https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8/query

La consulta utiliza parámetros como `where`, `outFields`, `returnGeometry` y `f` para solicitar los registros en formato JSON. También utiliza paginación para consultar los resultados por grupos.

## Datos obtenidos

La aplicación presenta los campos disponibles de cada registro, entre ellos código del paradero, nombre, vía, dirección, localidad, latitud, longitud y otros atributos que entrega el servicio. Los resultados se muestran en una tabla HTML y pueden consultarse seleccionando distintas localidades de Bogotá.

## Tecnologías utilizadas

- PHP para realizar las solicitudes y procesar los datos.
- HTML para estructurar el formulario y la tabla.
- CSS para diseñar la interfaz.
- API REST de ArcGIS para obtener la información geográfica.

## Ejecución

El proyecto se ejecuta localmente mediante XAMPP, con Apache activo, desde:

`http://localhost/paraderos-sitp/paraderos_sitp.php`

**Nota:** los resultados dependen de la disponibilidad del servicio externo y de los atributos que este devuelva.
