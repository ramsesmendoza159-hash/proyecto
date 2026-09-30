<?php
// helpers/SVGSanitizer.php
// Sanitizador profesional de SVG para prevenir XSS almacenado
// ✅ Usa DOMDocument (robusto frente a bypasses de regex)
// ✅ Whitelist estricta de tags y atributos
// ✅ Normalización previa de atributos para detectar bypasses con HTML entities
// ✅ Sanitización de referencias de URL (href, xlink:href, etc.)
// ✅ Preserva paths y atributos válidos de firmas manuscritas

if (!class_exists('SVGSanitizer')) {

    class SVGSanitizer
    {
        /**
         * Tags SVG permitidos (whitelist).
         */
        private static array $tagsPermitidos = [
            // Contenedores
            'svg', 'g', 'defs', 'symbol', 'marker', 'switch', 'a',
            // Formas
            'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect',
            // Texto
            'text', 'tspan', 'textPath', 'title', 'desc', 'metadata',
            // Gradientes
            'linearGradient', 'radialGradient', 'stop', 'pattern',
            // Filtros
            'filter', 'feGaussianBlur', 'feOffset', 'feBlend', 'feColorMatrix',
            'feComposite', 'feFlood', 'feMerge', 'feMergeNode', 'feMorphology',
            // Clipping / masking
            'clipPath', 'mask',
            // Estructura
            'use',
        ];

        /**
         * Atributos SVG permitidos (whitelist).
         */
        private static array $atributosPermitidos = [
            // Geometría
            'd', 'points', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
            'cx', 'cy', 'r', 'rx', 'ry', 'width', 'height',
            // Estilo
            'fill', 'fill-opacity', 'fill-rule',
            'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
            'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity',
            'opacity', 'color',
            // Transformación
            'transform', 'gradientTransform', 'patternTransform',
            'gradientUnits', 'patternUnits', 'clipPathUnits',
            // Estructura
            'viewBox', 'preserveAspectRatio', 'version', 'baseProfile',
            'xmlns', 'xmlns:xlink',
            // Identificadores
            'id', 'class', 'name',
            // Referencias
            'href', 'xlink:href',
            // Texto
            'font-family', 'font-size', 'font-weight', 'font-style',
            'text-anchor', 'dominant-baseline', 'letter-spacing',
            // Gradientes
            'offset', 'stop-color', 'stop-opacity',
            // Clipping
            'clip-path', 'mask', 'clip-rule',
            // Filtros
            'in', 'in2', 'result', 'stdDeviation', 'dx', 'dy',
            'mode', 'values', 'type', 'operator', 'k1', 'k2', 'k3', 'k4',
            // Patrones
            'patternContentUnits', 'patternUnits',
            // Marcadores
            'markerWidth', 'markerHeight', 'refX', 'refY', 'orient',
            // Switch
            'requiredFeatures', 'systemLanguage',
            // General
            'data-*',
        ];

        /**
         * Tags que SIEMPRE se eliminan con todo su contenido.
         */
        private static array $tagsProhibidos = [
            'script', 'style', 'foreignObject', 'iframe', 'embed', 'object',
            'link', 'meta', 'base', 'frame', 'frameset', 'applet', 'param',
            'audio', 'video', 'source', 'track',
            // SMIL (vectores de ataque por animación)
            'animate', 'animateMotion', 'animateTransform', 'set', 'handler',
        ];

        /**
         * Tamaño máximo del SVG (200 KB por defecto).
         */
        private static int $tamanoMaximo = 204800;

        // ==========================================
        // API PÚBLICA
        // ==========================================

        public static function sanitize($svg): string
        {
            if (empty($svg) || !is_string($svg)) {
                return '';
            }

            if (strlen($svg) > self::$tamanoMaximo) {
                error_log("SVGSanitizer::sanitize - SVG excede tamaño máximo (" . strlen($svg) . " bytes)");
                return '';
            }

            $svg = trim($svg);

            if (stripos($svg, '<svg') === false) {
                return '';
            }

            $doc = self::cargarDom($svg);
            if ($doc === null) {
                return '';
            }

            $svgNode = $doc->documentElement;
            if ($svgNode === null || strtolower($svgNode->nodeName) !== 'svg') {
                return '';
            }

            self::limpiarNodo($svgNode);

            $limpio = $doc->saveXML($svgNode);

            if ($limpio === false || empty($limpio)) {
                return '';
            }

            if (stripos($limpio, '<svg') === false || stripos($limpio, '</svg>') === false) {
                return '';
            }

            return $limpio;
        }

        public static function esFormatoValido($svg): bool
        {
            if (empty($svg) || !is_string($svg)) {
                return false;
            }
            if (strlen($svg) > self::$tamanoMaximo) {
                return false;
            }
            return stripos($svg, '<svg') !== false && stripos($svg, '</svg>') !== false;
        }

        public static function setTamanoMaximo(int $bytes): void
        {
            self::$tamanoMaximo = max(1024, $bytes);
        }

        // ==========================================
        // INTERNO
        // ==========================================

        private static function cargarDom(string $svg): ?DOMDocument
        {
            $previo = libxml_use_internal_errors(true);
            $doc = new DOMDocument('1.0', 'UTF-8');

            $opciones = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING;
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . $svg;

            $ok = $doc->loadXML($xml, $opciones);

            libxml_clear_errors();
            libxml_use_internal_errors($previo);

            return $ok ? $doc : null;
        }

        private static function limpiarNodo(DOMNode $nodo): void
        {
            if ($nodo->hasChildNodes()) {
                $hijos = [];
                foreach ($nodo->childNodes as $hijo) {
                    $hijos[] = $hijo;
                }

                foreach ($hijos as $hijo) {
                    if ($hijo instanceof DOMElement) {
                        $tag = strtolower($hijo->nodeName);

                        if (in_array($tag, self::$tagsProhibidos, true)) {
                            $nodo->removeChild($hijo);
                            continue;
                        }

                        if (!in_array($tag, self::$tagsPermitidos, true)) {
                            $nodo->removeChild($hijo);
                            continue;
                        }

                        self::limpiarAtributos($hijo);
                        self::limpiarNodo($hijo);

                    } elseif ($hijo instanceof DOMComment || $hijo instanceof DOMProcessingInstruction) {
                        $nodo->removeChild($hijo);

                    } elseif ($hijo instanceof DOMCdataSection) {
                        $texto = $hijo->nodeValue;
                        $textoNode = $nodo->ownerDocument->createTextNode($texto);
                        $nodo->replaceChild($textoNode, $hijo);
                    }
                }
            }
        }

        private static function limpiarAtributos(DOMElement $el): void
        {
            if (!$el->hasAttributes()) {
                return;
            }

            // Copia estática de los atributos para evitar problemas de mutación durante la iteración
            $atributos = [];
            foreach ($el->attributes as $attr) {
                $atributos[] = $attr;
            }

            foreach ($atributos as $attr) {
                $nombre = $attr->nodeName;
                $nombreLower = strtolower($nombre);
                $valor = $attr->nodeValue ?? '';

                // Decodificar entidades HTML y remover caracteres de control/espacios para prevenir elusiones
                $valorNormalizado = html_entity_decode($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $valorNormalizado = preg_replace('/[\x00-\x20\x7F-\x9F]/u', '', $valorNormalizado);
                $valorNormalizadoLower = strtolower($valorNormalizado);

                // 1. Bloquear manejadores de eventos (on*)
                if (strpos($nombreLower, 'on') === 0 && strlen($nombreLower) > 2) {
                    self::removerAtributoSeguro($el, $attr);
                    continue;
                }

                // 2. Bloquear esquemas URI peligrosos (javascript:, data:, vbscript:, etc.)
                if (
                    strpos($valorNormalizadoLower, 'javascript:') !== false ||
                    strpos($valorNormalizadoLower, 'data:') !== false ||
                    strpos($valorNormalizadoLower, 'vbscript:') !== false ||
                    strpos($valorNormalizadoLower, 'livescript:') !== false
                ) {
                    self::removerAtributoSeguro($el, $attr);
                    continue;
                }

                // 3. Restringir referencias externas en atributos de enlace
                if (in_array($nombreLower, ['href', 'xlink:href', 'src', 'action', 'formaction'], true)) {
                    if (!preg_match('/^#[a-zA-Z0-9_\-:]+$/', trim($valor))) {
                        self::removerAtributoSeguro($el, $attr);
                        continue;
                    }
                }

                // 4. Whitelist de atributos
                $permitido = false;
                foreach (self::$atributosPermitidos as $permitidoNom) {
                    if ($permitidoNom === 'data-*' && strpos($nombreLower, 'data-') === 0) {
                        $permitido = true;
                        break;
                    }
                    if (strtolower($permitidoNom) === $nombreLower) {
                        $permitido = true;
                        break;
                    }
                }

                // 5. Permitir declaraciones de namespace válidas
                if (!$permitido) {
                    if (
                        strpos($nombreLower, 'xmlns:') === 0 ||
                        strpos($nombreLower, 'xlink:') === 0 ||
                        $nombreLower === 'xmlns'
                    ) {
                        $permitido = true;
                    }
                }

                if (!$permitido) {
                    self::removerAtributoSeguro($el, $attr);
                }
            }
        }

        /**
         * Elimina un atributo de forma segura independientemente de si utiliza namespaces.
         */
        private static function removerAtributoSeguro(DOMElement $el, DOMAttr $attr): void
        {
            try {
                if ($attr->namespaceURI) {
                    $el->removeAttributeNS($attr->namespaceURI, $attr->localName);
                } else {
                    $el->removeAttribute($attr->nodeName);
                }
            } catch (Throwable $e) {
                error_log("SVGSanitizer: Error al remover el atributo {$attr->nodeName}: " . $e->getMessage());
            }
        }
    }
}