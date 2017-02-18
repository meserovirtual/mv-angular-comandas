(function () {
    'use strict';

    var scripts = document.getElementsByTagName("script");
    var currentScriptPath = scripts[scripts.length - 1].src;

    if (currentScriptPath.length == 0) {
        currentScriptPath = window.installPath + '/ac-angular-cocina/includes/ac-cocina.php';
    }

    angular.module('acCocina', [])
        .factory('CocinaService', CocinaService)
        .service('CocinaVars', CocinaVars)
    ;


    CocinaService.$inject = ['$http', 'CocinaVars', '$cacheFactory', 'AcUtils', '$q', '$location', 'AcUtilsGlobals', 'ErrorHandler'];
    function CocinaService($http, CocinaVars, $cacheFactory, AcUtils, $q, $location, AcUtilsGlobals, ErrorHandler) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('ac-cocina.js', '/includes/ac-cocina.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;
        service.getMasVendidos = getMasVendidos;
        service.getByCategoria = getByCategoria;

        service.create = create;

        service.update = update;

        service.remove = remove;
        service.save = save;

        return service;

        //Functions

        /**
         * Función que determina si es un update o un create
         * @param cocina
         * @returns {*}
         */
        function save(cocina) {

            var deferred = $q.defer();

            if (cocina.cocina_id != undefined) {
                deferred.resolve(update(cocina));
            } else {
                deferred.resolve(create(cocina));
            }
            return deferred.promise;
        }

        /**
         * @description Obtiene todos los cocina
         * @param callback
         * @returns {*}
         */
        function get() {
            AcUtilsGlobals.startWaiting();
            var urlGet = url + '?function=getCocina';
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de cocina
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (CocinaVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    var deferred = $q.defer();
                    cachedData = $httpDefaultCache.get(urlGet);
                    deferred.resolve(cachedData);
                    AcUtilsGlobals.stopWaiting();
                    return deferred.promise;
                }
            }


            return $http.get(urlGet, {cache: true})
                .then(function (response) {

                        for (var i = 0; i < response.data.length; i++) {
                            response.data[i].precios[0].precio = parseFloat(response.data[i].precios[0].precio);
                            response.data[i].precios[1].precio = parseFloat(response.data[i].precios[1].precio);
                            response.data[i].precios[2].precio = parseFloat(response.data[i].precios[2].precio);
                            response.data[i].precios.sort(function (a, b) {
                                // Turn your strings into dates, and then subtract them
                                // to get a value that is either negative, positive, or zero.
                                return a.precio_tipo_id - b.precio_tipo_id;
                            });
                            response.data[i].pto_repo = parseFloat(response.data[i].pto_repo);
                            response.data[i].iva = parseFloat(response.data[i].iva);
                            response.data[i].status = '' + response.data[i].status;
                            response.data[i].en_oferta = '' + response.data[i].en_oferta;
                            response.data[i].en_slider = '' + response.data[i].en_slider;
                            response.data[i].destacado = '' + response.data[i].destacado;
                            response.data[i].cocina_tipo = '' + response.data[i].cocina_tipo;

                        }

                        $httpDefaultCache.put(urlGet, response.data);
                        CocinaVars.clearCache = false;
                        CocinaVars.paginas = (response.data.length % CocinaVars.paginacion == 0) ? parseInt(response.data.length / CocinaVars.paginacion) : parseInt(response.data.length / CocinaVars.paginacion) + 1;
                        AcUtilsGlobals.stopWaiting();
                        return response.data;
                    }
                )
                .catch(function (response) {
                    CocinaVars.clearCache = true;
                    AcUtilsGlobals.stopWaiting();
                    ErrorHandler(response);
                })
        }


        /**
         * @description Retorna la lista filtrada de cocina
         * @param params -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param values
         * @param exact_match
         */
        function getByParams(params, values, exact_match) {
            return get().then(function (data) {
                return AcUtils.getByParams(params, values, exact_match, data);
            }).then(function (data) {
                return data;
            });
        }

        /**
         * @description Retorna los primero 8 cocina mas vendidos
         * @param callback
         */
        function getMasVendidos(callback) {
            get(function (data) {
                var response = data.sort(function (a, b) {
                    return b.vendidos - a.vendidos;
                });

                callback(response.slice(0, 8));
            });
        }

        /**
         * @description Retorna un listado de cocina filtrando por la categoria
         * @param categoria_id
         * @param callback
         */
        function getByCategoria(categoria_id, callback) {
            var cocina = [];
            get(function (data) {
                if (data == undefined || data.length == 0)
                    return callback(cocina);

                data.forEach(function (cocina) {
                    if (cocina === undefined || cocina.categorias === undefined || cocina.categorias.length == 0)
                        return callback(cocina);

                    if (categoria_id == cocina.categorias[0].categoria_id)
                        cocina.push(cocina);
                });
                return callback(cocina);
            });
        }

        /** @name: remove
         * @param cocina_id
         * @param callback
         * @description: Elimina el cocina seleccionado.
         */
        function remove(cocina_id, callback) {
            return $http.post(url,
                {function: 'removeCocina', 'cocina_id': cocina_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        CocinaVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un cocina.
         * @param cocina
         * @returns {*}
         */
        function create(cocina) {

            return $http.post(url,
                {
                    'function': 'createCocina',
                    'cocina': JSON.stringify(cocina)
                })
                .then(function (response) {
                    CocinaVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    CocinaVars.clearCache = true;
                    ErrorHandler(response)
                });
        }


        /** @name: update
         * @param cocina
         * @description: Realiza update al cocina.
         */
        function update(cocina) {
            return $http.post(url,
                {
                    'function': 'updateCocina',
                    'cocina': JSON.stringify(cocina)
                })
                .then(function (response) {
                    CocinaVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    CocinaVars.clearCache = true;
                    ErrorHandler(response.data)
                });
        }

    }

    CocinaVars.$inject = [];
    /**
     * @description Almacena variables temporales de cocina
     * @constructor
     */
    function CocinaVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

    }



})();