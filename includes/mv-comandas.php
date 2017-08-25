<?php
session_start();


// Token

if (file_exists('../../../includes/MyDBi.php')) {
    require_once '../../../includes/MyDBi.php';
    require_once '../../../includes/utils.php';
} else {
    require_once 'MyDBi.php';
}


class Comandas extends Main
{
    private static $instance;

    public static function init($decoded)
    {
        self::$instance = new Main(get_class(), $decoded['function']);
        try {
            call_user_func(get_class() . '::' . $decoded['function'], $decoded);
        } catch (Exception $e) {

            $file = 'error.log';
            $current = file_get_contents($file);
            $current .= date('Y-m-d H:i:s') . ": " . $e . "\n";
            file_put_contents($file, $current);

            header('HTTP/1.0 500 Internal Server Error');
            echo $e;
        }
    }

    /* @name: get
     * @param
     * @description: Obtiene todos los usuario con sus direcciones.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function getComanda($params)
    {
        $db = self::$instance->db;
        $decoded = json_decode($params["params"]);
        $mesa_id = getDataFromToken('mesa_id');
        $session_id = getDataFromToken('session_id');
        $results = $db->rawQuery('SELECT
    c.comanda_id,
    c.status,
    c.total,
    c.origen_id,
    c.fecha,
    c.envio_id,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status AS platoStatus,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad
FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
        LEFT JOIN
    envios e ON e.envio_id = c.envio_id
' . (($mesa_id != null) ? ' WHERE c.mesa_id = ' . $mesa_id . ' ' : ' WHERE cd.status != 0 ') . ' AND c . status <> 5 
GROUP BY c . comanda_id , c . status , cd . comanda_detalle_id , cd . producto_id , p . nombre , cd . status , cd . comentarios , cd . cantidad , ce . comanda_extra_id , ce . producto_id , pp . nombre , ce . cantidad;
');


        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                    'comanda_id' => $row["comanda_id"],
                    'status' => $row["status"],
                    'total' => $row["total"],
                    'origen_id' => $row["origen_id"],
                    'fecha' => $row["fecha"],
                    'envio_id' => $row["envio_id"],
                    'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'extras' => array()
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'extras' => array()
                    );

//                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array(
//                        'comanda_detalle_id' => $row['comanda_detalle_id'],
//                        'producto_id' => $row['producto_id'],
//                        'nombre' => $row['nombre'],
//                        'precio' => $row['precio'],
//                        'status' => $row['status'],
//                        'comentarios' => $row['comentarios'],
//                        'cantidad' => $row['cantidad']
//                    ));
                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }


        }
        echo json_encode(array_values($final));
    }


    function getComandaByMesa($params)
    {
        $db = self::$instance->db;
        $decoded = json_decode($params["params"]);
        //$mesa_id = getDataFromToken('mesa_id');
        //$session_id = getDataFromToken('session_id');
        $results = $db->rawQuery('SELECT
    c.comanda_id,
    c.status,
    c.total,
    c.origen_id,
    c.fecha,
    c.envio_id,
    c.usuario_id,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status AS platoStatus,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad
FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
        LEFT JOIN
    envios e ON e.envio_id = c.envio_id
WHERE c.mesa_id = ' . $params["mesa_id"] . ' AND c.status <> 5
GROUP BY c . comanda_id , c . status , cd . comanda_detalle_id , cd . producto_id , p . nombre , cd . status , cd . comentarios , cd . cantidad , ce . comanda_extra_id , ce . producto_id , pp . nombre , ce . cantidad;
');

        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                  'comanda_id' => $row["comanda_id"],
                  'status' => $row["status"],
                  'total' => $row["total"],
                  'origen_id' => $row["origen_id"],
                  'fecha' => $row["fecha"],
                  'envio_id' => $row["envio_id"],
                  'usuario_id' => $row["usuario_id"],
                  'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                      'comanda_detalle_id' => $row['comanda_detalle_id'],
                      'producto_id' => $row['producto_id'],
                      'nombre' => $row['nombre'],
                      'precio' => $row['precio'],
                      'platoStatus' => $row['platoStatus'],
                      'session_id' => $row['session_id'],
                      'comentarios' => $row['comentarios'],
                      'cantidad' => $row['cantidad'],
                      'extras' => array()
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                      'comanda_detalle_id' => $row['comanda_detalle_id'],
                      'producto_id' => $row['producto_id'],
                      'nombre' => $row['nombre'],
                      'precio' => $row['precio'],
                      'platoStatus' => $row['platoStatus'],
                      'session_id' => $row['session_id'],
                      'comentarios' => $row['comentarios'],
                      'cantidad' => $row['cantidad'],
                      'extras' => array()
                    );

//                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array(
//                        'comanda_detalle_id' => $row['comanda_detalle_id'],
//                        'producto_id' => $row['producto_id'],
//                        'nombre' => $row['nombre'],
//                        'precio' => $row['precio'],
//                        'status' => $row['status'],
//                        'comentarios' => $row['comentarios'],
//                        'cantidad' => $row['cantidad']
//                    ));
                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }
        }
        echo json_encode(array_values($final));
    }


    function getPlatosTiempoEsperaExcedido($params)
    {
        $db = self::$instance->db;
        $decoded = json_decode($params["params"]);
        $mesa_id = getDataFromToken('mesa_id');
        $session_id = getDataFromToken('session_id');
        $results = $db->rawQuery('SELECT
    c.comanda_id,
    c.status,
    c.total,
    c.origen_id,
    c.fecha,
    c.envio_id,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status AS platoStatus,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    cd.preparacion_inicio,
    p.tiempo_espera,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad
FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
        LEFT JOIN
    envios e ON e.envio_id = c.envio_id
WHERE c.status IN (0,1,2,3) AND cd.status = 1
GROUP BY c.comanda_id, c.status, cd.comanda_detalle_id, cd.producto_id, p.nombre, cd.status, cd.comentarios, cd.cantidad, ce.comanda_extra_id, ce.producto_id, pp.nombre, ce.cantidad;
');


        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                    'comanda_id' => $row["comanda_id"],
                    'status' => $row["status"],
                    'total' => $row["total"],
                    'origen_id' => $row["origen_id"],
                    'fecha' => $row["fecha"],
                    'envio_id' => $row["envio_id"],
                    'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'preparacion_inicio' => $row['preparacion_inicio'],
                        'tiempo_espera' => $row['tiempo_espera'],
                        'extras' => array()
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'preparacion_inicio' => $row['preparacion_inicio'],
                        'tiempo_espera' => $row['tiempo_espera'],
                        'extras' => array()
                    );

                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }


        }
        echo json_encode(array_values($final));

    }


    function getComandaNoEntregadas($params) {
        $db = self::$instance->db;
        $decoded = json_decode($params["params"]);
        //$mesa_id = getDataFromToken('mesa_id');
        //$session_id = getDataFromToken('session_id');


        $comandas = $db->rawQuery('
            SELECT comanda_id
            FROM comandas
            WHERE 	HOUR(TIMEDIFF(CURRENT_TIMESTAMP(), fecha)) = 0 AND
		                MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(), fecha)) <= 30 AND
		                status IN (1,2);
        ');

        $comandaIn = '';
        if(count($comandas) > 0) {
            foreach ($comandas as $row) {
                $comandaIn = $comandaIn . $row["comanda_id"] . ',';
            }
            $comandaIn = substr($comandaIn, 0, -1);
        } else {
            $comandaIn = '0';
        }


        $results = $db->rawQuery('SELECT
    c.comanda_id,
    c.status,
    c.total,
    c.origen_id,
    c.fecha,
    c.envio_id,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status AS platoStatus,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad
FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
 WHERE c.comanda_id NOT IN ('. $comandaIn .') AND c.status IN (1,2)
GROUP BY c . comanda_id , c . status , cd . comanda_detalle_id , cd . producto_id , p . nombre , cd . status , cd . comentarios , cd . cantidad , ce . comanda_extra_id , ce . producto_id , pp . nombre , ce . cantidad;
');


        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                    'comanda_id' => $row["comanda_id"],
                    'status' => $row["status"],
                    'total' => $row["total"],
                    'origen_id' => $row["origen_id"],
                    'fecha' => $row["fecha"],
                    'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad']
                    );

                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }


        }
        echo json_encode(array_values($final));
    }


    function getPedidosWeb()
    {
        $db = self::$instance->db;
        $results = $db->rawQuery('SELECT
    c.comanda_id,
    c.status,
    c.total,
    c.origen_id,
    c.fecha,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status AS platoStatus,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad,
    e.envio_id,
    e.fecha fecha_envio,
    e.total total_envio,
    e.calle,
    e.nro,
    e.status status_envio,
    e.descuento,
    u.apellido,
    u.nombre nombre_cliente,
    u.mail,
    u.telefono
    FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
        LEFT JOIN
    envios e ON e.envio_id = c.envio_id
        LEFT JOIN
    usuarios u ON u.usuario_id = e.usuario_id
    WHERE cd.status <> 5 AND origen_id = -2
GROUP BY c.comanda_id,c.status,cd.comanda_detalle_id,cd.producto_id,p.nombre,cd.status,cd.comentarios,cd.cantidad,ce.comanda_extra_id,ce.producto_id,pp.nombre,ce.cantidad;
');


        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                    'comanda_id' => $row["comanda_id"],
                    'status' => $row["status"],
                    'total' => $row["total"],
                    'origen_id' => $row["origen_id"],
                    'fecha' => $row["fecha"],
                    'envio_id' => $row["envio_id"],
                    'fecha_envio' => $row["fecha_envio"],
                    'total_envio' => $row["total_envio"],
                    'calle' => $row["calle"],
                    'nro' => $row["nro"],
                    'status_envio' => $row["status_envio"],
                    'descuento' => $row["descuento"],
                    'apellido' => $row["apellido"],
                    'nombre_cliente' => $row["nombre_cliente"],
                    'mail' => $row["mail"],
                    'telefono' => $row["telefono"],
                    'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'extras' => array()
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'platoStatus' => $row['platoStatus'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad'],
                        'extras' => array()
                    );

//                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array(
//                        'comanda_detalle_id' => $row['comanda_detalle_id'],
//                        'producto_id' => $row['producto_id'],
//                        'nombre' => $row['nombre'],
//                        'precio' => $row['precio'],
//                        'status' => $row['status'],
//                        'comentarios' => $row['comentarios'],
//                        'cantidad' => $row['cantidad']
//                    ));
                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }


        }
        echo json_encode(array_values($final));
    }

    /**
     * @description Crea un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function createComanda($params)
    {

        $db = self::$instance->db;
        $decoded = self::checkComanda(json_decode($params["comanda"]));
        $SQL = 'select * from comandas where mesa_id = ' . $decoded->mesa_id . ' and status = 0';
        $result = $db->rawQuery($SQL);

//        echo 'Creo Comanda ';
        $db->startTransaction();

        if (sizeof($result) > 0) {
            $SQL2 = 'update comandas set total = (select sum(precio) from comandas_detalles where comanda_id = ' . $result[0]['comanda_id'] . ') where comanda_id = ' . $result[0]['comanda_id'] . ' and status = 0';
            $db->rawQuery($SQL2);

            $result = $result[0]['comanda_id'];
        } else {
            $data = array(
                'usuario_id' => $decoded->usuario_id,
                'status' => $decoded->status,
                'mesa_id' => $decoded->mesa_id,
                //'total' => 0,
                'total' => $decoded->total,
                'origen_id' => $decoded->origen_id,
                'envio_id' => -1,
            );
            $result = $db->insert('comandas', $data);
        }

        if ($result > -1) {
//            foreach ($decoded->kits as $detalle) {
            $decoded->comanda_id = $result;
            $aux = self::createDetalle($decoded, $db);
            echo $aux;
            //if (!self::createDetalle($decoded, $db)) {
            if ($aux < 0) {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
                return;
            }
//            }

            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Crea un precio para un comanda determinado
     * @param $precio
     * @param $comanda_id
     * @param $db
     * @return bool
     */
    function createDetalle($params, $db = null)
    {
//        echo 'Creo Detalle ';

        $innerCall = true;
        if ($db === null) {
            $db = self::$instance->db;
            $innerCall = false;
            $db->startTransaction();
            $decoded = self::checkDetalles(json_decode($params["detalle"]));
        } else {
            //$decoded = self::checkDetalles($params);
            $decoded = self::checkDetalles($params->detalles);
        }

        foreach($decoded as $detalle){
            $data = array(
                'producto_id' => $detalle->producto_id,
                'status' => $detalle->status,
                'comentarios' => $detalle->comentarios,
                'comanda_id' => $params->comanda_id,
                'cantidad' => $detalle->cantidad,
                'precio' => $detalle->precio,
                'session_id' => getDataFromToken('session_id'),
                'usuario_id' => ($decoded->usuario_id == null) ? -2 : $decoded->usuario_id == null,
            );

            $results = $db->insert('comandas_detalles', $data);
        }
        /*
        $data = array(
            'producto_id' => $decoded->producto_id,
            'status' => 1,
            'comentarios' => $decoded->comentarios,
            'comanda_id' => $decoded->comanda_id,
            'cantidad' => $decoded->cantidad,
            'precio' => $decoded->precios[0]->precio,
            'session_id' => getDataFromToken('session_id'),
            'usuario_id' => ($decoded->usuario_id == null) ? -2 : $decoded->usuario_id == null,
        );

        $results = $db->insert('comandas_detalles', $data);
        */

        if ($innerCall) {
            foreach ($decoded->kits as $extra) {
                $extra->comanda_detalle_id = $results;
                if ($extra->selected == 'true' && $extra->opcional == 1) {
                    if (!self::createExtra($extra, $db)) {
                        $db->rollback();
                        header('HTTP / 1.0 500 Internal Server Error');
                        echo $db->getLastError();
                        return false;
                    }
                }
            }
        } else {

            foreach ($decoded->kits as $extra) {
                $extra->comanda_detalle_id = $results;
                if ($extra->selected == true && $extra->opcional == 1) {
                    if (!self::createExtra($extra, $db)) {
                        $db->rollback();
                        header('HTTP / 1.0 500 Internal Server Error');
                        echo $db->getLastError();
                        return false;
                    }
                }
            }

            if ($results > -1) {
                $db->commit();
                header('HTTP / 1.0 200 Ok');
                echo json_encode($results);
            } else {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
            }
        }

        $aux1 = ($results > -1) ? true : false;
        return $aux1;
        //return $results > -1;
    }

    /**
     * @description Crea la relación entre un comanda y una categoría
     * @param $categoria
     * @param $comanda_id
     * @param $db
     * @return bool
     */
    function createExtra($params, $db = null)
    {
        echo 'Creo Extra ';

        $innerCall = true;
        if ($db === null) {
            $db = self::$instance->db;
            $innerCall = false;
            $db->startTransaction();
            $decoded = self::checkExtras(json_decode($params["extra"]));
        } else {
            //$decoded = self::checkExtras($params);
            $decoded = self::checkExtras($params->kits);
        }

        foreach($decoded as $extra){
            $data = array(
                'producto_id' => $extra->producto_id,
                'cantidad' => $extra->cantidad,
                'comanda_detalle_id' => $extra->comanda_detalle_id,
                'precio' => $extra->precio
            );

            $results = $db->insert('comandas_extras', $data);
        }
        /*
        $data = array(
            'producto_id' => $decoded->producto_id,
            'cantidad' => $decoded->cantidad,
            'comanda_detalle_id' => $decoded->comanda_detalle_id,
            'precio' => $decoded->precio
        );

        $results = $db->insert('comandas_extras', $data);
        */
        if ($innerCall) {
            return ($results > -1) ? true : false;
        } else {

            if ($results > -1) {
                $db->commit();
                header('HTTP / 1.0 200 Ok');
                echo json_encode($results);
            } else {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
            }
        }

        return $results > -1;
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function updateComanda($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkComanda(json_decode($params["comanda"]));

        echo 'comanda_id: ' . $decoded->comanda_id . ' - ';

        $db->where('comanda_id', $decoded->comanda_id);

        $data = array(
            'usuario_id' => $decoded->usuario_id,
            'status' => $decoded->status,
            'mesa_id' => $decoded->mesa_id,
            'total' => $decoded->total,
            'origen_id' => $decoded->origen_id,
            'envio_id' => -1,
        );

        $result = $db->update('comandas', $data);


//        Borro extras
        $SQL = "delete from comandas_extras where comanda_detalle_id in (select comanda_detalle_id from comandas_detalles where comanda_id = " . $decoded->comanda_id . ")";
        $db->rawQuery($SQL);

//        borro detalles
        $db->where('comanda_id', $decoded->comanda_id);
        $db->delete('comandas_detalles');


        if ($result) {
            foreach ($decoded->detalles as $detalle) {

                $detalle['comanda_id'] = $result;
                if (!self::createDetalle(json_encode(array('detalle' => $detalle), $db))) {
                    $db->rollback();
                    header('HTTP / 1.0 500 Internal Server Error');
                    echo $db->getLastError();
                    return;
                }
            }

            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }



    function updateStatusPlato($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkDetalles(json_decode($params["detalle"]));
        echo 'Detalle_id: ' . $decoded->comanda_detalle_id . ' - ';

        $db->where('comanda_detalle_id', $decoded->comanda_detalle_id);

        $data = array(
            'status' => $decoded->status,
            'preparacion_fin' => $db->now()
        );

        $result = $db->update('comandas_detalles', $data);

        if ($result) {
            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    function updateStatusComanda($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkComanda(json_decode($params["comanda"]));
        echo 'Detalle_id: ' . $decoded->comanda_detalle_id . ' - ';

        $db->where('comanda_id', $decoded->comanda_id);

        $data = array(
            'status' => $decoded->status
        );

        $result = $db->update('comandas', $data);

        if ($result) {
            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function updatePedido($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkComanda(json_decode($params["comanda"]));


        $db->where('comanda_id', $decoded->comanda_id);

        $data = array(
            'status' => 2
        );

        $result = $db->update('comandas', $data);


//        Borro extras
        $SQL = "delete from comandas_extras where comanda_detalle_id in (select comanda_detalle_id from comandas_detalles where comanda_id = " . $decoded->comanda_id . ")";
        $db->rawQuery($SQL);

//        borro detalles
        $db->where('comanda_id', $decoded->comanda_id);
        $db->delete('comandas_detalles');


        if ($result) {
            foreach ($decoded->detalles as $detalle) {

                $detalle['comanda_id'] = $result;
                if (!self::createDetalle(json_encode(array('detalle' => $detalle), $db))) {
                    $db->rollback();
                    header('HTTP / 1.0 500 Internal Server Error');
                    echo $db->getLastError();
                    return;
                }
            }

            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }


    function ordenar($params){
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = json_decode($params["comanda_id"]);


        $db->where('comanda_id', $decoded);
        $db->where('status', 0);

        $data = array(
            'status' => 1
        );

        $result = $db->update('comandas_detalles', $data);

        if ($result) {
            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function quitar($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = $params["comanda_detalle_id"];

        echo 'comanda_detalle_id: ' . $params["comanda_detalle_id"] . ' - ';

//        $SQL = "DELETE FROM comandas_extras WHERE comanda_detalle_id = " . $decoded;
        $db->where('comanda_detalle_id', $decoded);
        $result = $db->delete('comandas_extras');
        $db->where('comanda_detalle_id', $decoded);
        $result = $db->delete('comandas_detalles');
//        $result = $db->rawQuery($SQL);

//        $SQL = "DELETE FROM comandas_detalles WHERE comanda_detalle_id = " . $decoded;
//        $result = $db->rawQuery($SQL);


        if ($result) {
            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Verifica todos los campos de comanda para que existan
     * @param $comanda
     * @return mixed
     */
    function checkComanda($comanda)
    {
        //$comanda->comanda_id = (!array_key_exists("comanda_id", $comanda)) ? -1 : $comanda->comanda_id;
        $comanda->usuario_id = (!array_key_exists("usuario_id", $comanda)) ? -2 : $comanda->usuario_id;
        $comanda->status = (!array_key_exists("status", $comanda)) ? 0 : $comanda->status;
        $comanda->mesa_id = (!array_key_exists("mesa_id", $comanda)) ? -2 : $comanda->mesa_id;
        $comanda->total = (!array_key_exists("total", $comanda)) ? 0.0 : $comanda->total;
        $comanda->origen_id = (!array_key_exists("origen_id", $comanda)) ? -2 : $comanda->origen_id;
        $comanda->envio_id = (!array_key_exists("envio_id", $comanda)) ? -1 : $comanda->envio_id;
        $comanda->detalles = (!array_key_exists("detalles", $comanda)) ? array() : self::checkDetalles($comanda->detalles);

        return $comanda;
    }

    /**
     * @description Verifica todos los campos de Proveedores y Comandas existan
     * @param $comandas_proveedores
     * @return mixed
     */
    function checkDetalles($detalles)
    {
        foreach ($detalles as $detalle) {
            $detalle->producto_id = (!array_key_exists("producto_id", $detalle)) ? 0 : $detalle->producto_id;
            $detalle->status = (!array_key_exists("status", $detalle)) ? '' : $detalle->status;
            $detalle->preparacion_fin = (!array_key_exists("preparacion_fin", $detalle)) ? '' : $detalle->preparacion_fin;
            $detalle->comentarios = (!array_key_exists("comentarios", $detalle)) ? '' : $detalle->comentarios;
            $detalle->comanda_id = (!array_key_exists("comanda_id", $detalle)) ? '' : $detalle->comanda_id;
            $detalle->cantidad = (!array_key_exists("cantidad", $detalle)) ? '' : $detalle->cantidad;
            $detalle->precio = (!array_key_exists("precio", $detalle)) ? '' : $detalle->precio;
            $detalle->extras = (!array_key_exists("extras", $detalle)) ? array() : self::checkExtras($detalle->extras);
        }
        return $detalles;
    }

    /**
     * @description Verifica todos los campos de fotos para que existan
     * @param $fotos
     * @return mixed
     */
    function checkExtras($extras)
    {
        foreach ($extras as $extra) {
            $extra->producto_id = (!array_key_exists("producto_id", $extra)) ? -2 : $extra->producto_id;
            $extra->cantidad = (!array_key_exists("cantidad", $extra)) ? -2 : $extra->cantidad;
            $extra->comanda_detalle_id = (!array_key_exists("comanda_detalle_id", $extra)) ? -2 : $extra->comanda_detalle_id;
            $extra->precio = (!array_key_exists("precio", $extra)) ? 0.0 : $extra->precio;
            $extra->selected = (!array_key_exists("selected", $extra)) ? false : $extra->selected;
        }
        return $extras;
    }




}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents("php://input");
    $decoded = json_decode($data);
    Comandas::init(json_decode(json_encode($decoded), true));
} else {
    Comandas::init($_GET);
}



