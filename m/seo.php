<?php
/**
 * FAQ y Schema JSON-LD - Catálogo Móvil Ciberaula
 * v3.1 - Fix: eliminados atributos microdata de render_faq() para evitar
 *         duplicación de schema FAQPage (JSON-LD en head + microdata en body)
 */

// ============================================================
// BANCO DE PREGUNTAS FRECUENTES
// ============================================================

function get_faq_portada() {
    return [
        [
            'q' => '¿Qué es la formación bonificada y cómo funciona?',
            'a' => 'Es un sistema por el que las empresas pueden formar a sus trabajadores sin coste, recuperando la inversión a través de bonificaciones en las cuotas de la Seguridad Social. FUNDAE es el organismo público que lo gestiona. Toda empresa que cotice por formación profesional tiene derecho a un crédito anual para formar a su plantilla.',
        ],
        [
            'q' => '¿Quién puede acceder a estos cursos?',
            'a' => 'Todos los trabajadores en régimen general de la Seguridad Social cuya empresa cotice por formación profesional: empleados con contrato indefinido, temporal, a tiempo parcial o fijos discontinuos. Los autónomos con trabajadores a cargo también pueden bonificar la formación de su plantilla.',
        ],
        [
            'q' => '¿Cuánto le cuesta a mi empresa?',
            'a' => 'Puede ser completamente gratuito. Los cursos se financian con el crédito que cada empresa tiene asignado por FUNDAE. Las empresas de 1 a 5 empleados disponen de un mínimo de 420 euros anuales; las de mayor tamaño, un importe proporcional a su cotización. En la práctica, la mayoría de cursos se bonifican al 100%.',
        ],
        [
            'q' => '¿Cómo se gestiona el crédito de formación?',
            'a' => 'Cada año FUNDAE asigna un crédito a cada empresa en función de su plantilla y cotización del año anterior. El proceso es: elegir el curso, comunicarlo a FUNDAE con al menos 7 días de antelación, realizar la formación y aplicar la bonificación en los boletines de cotización. Nosotros nos encargamos de toda la gestión sin coste adicional.',
        ],
        [
            'q' => '¿Puedo estudiar en horario laboral o fuera de él?',
            'a' => 'Ambas opciones. La plataforma está disponible las 24 horas, los 7 días de la semana. Si se realiza en horario laboral, las horas cuentan como tiempo de trabajo. Lo habitual es combinar ambos horarios con total flexibilidad.',
        ],
        [
            'q' => '¿Qué diploma se obtiene al finalizar?',
            'a' => 'Al completar el curso se emite un diploma acreditativo oficial con el sello de FUNDAE, que certifica las horas lectivas y los contenidos cursados. Este diploma tiene reconocimiento institucional y es puntuable en numerosos tribunales de oposiciones y concursos públicos, además de ser válido para auditorías de formación y procesos de selección profesional.',
        ],
        [
            'q' => '¿Cuánto dura cada curso y qué plazo tengo?',
            'a' => 'La duración varía entre 20 y 100 horas lectivas según el curso. El plazo para completarlo se acuerda con la empresa, habitualmente entre 1 y 3 meses. La fecha de inicio se elige libremente, comunicándolo a FUNDAE con al menos 7 días de antelación.',
        ],
    ];
}

function get_faq_categoria($nombreCategoria) {
    $nc = htmlspecialchars($nombreCategoria, ENT_QUOTES, 'UTF-8');
    return [
        [
            'q' => '¿Estos cursos son bonificables por FUNDAE?',
            'a' => "Sí, todos los cursos de {$nc} de nuestro catálogo son 100% bonificables. Su empresa puede recuperar el coste íntegro mediante las cuotas de la Seguridad Social. Nos encargamos de toda la gestión sin coste adicional.",
        ],
        [
            'q' => '¿Qué modalidades de estudio están disponibles?',
            'a' => 'Ofrecemos tres modalidades: teleformación con tutor (acceso 24 horas a la plataforma), clases en vivo por aula virtual, y modalidad mixta que combina ambas. Las tres son bonificables al 100% y el alumno recibe diploma acreditativo con sello de FUNDAE al finalizar.',
        ],
        [
            'q' => '¿De cuánto tiempo dispongo para completar la formación?',
            'a' => 'El plazo es flexible y se adapta a las necesidades de su empresa. Habitualmente se dispone de entre 1 y 3 meses desde el inicio, con acceso a la plataforma las 24 horas. La fecha de inicio se elige libremente, comunicándolo a FUNDAE con 7 días de antelación.',
        ],
        [
            'q' => '¿Cómo funciona la bonificación?',
            'a' => 'El proceso es sencillo: su empresa elige el curso, nosotros comunicamos la formación a FUNDAE, los alumnos realizan el curso online y al finalizar su empresa aplica la bonificación en las cuotas de la Seguridad Social. El resultado es que la formación no tiene coste.',
        ],
    ];
}

function get_faq_curso($nombreCurso, $horas) {
    $nc = htmlspecialchars($nombreCurso, ENT_QUOTES, 'UTF-8');
    $duracion = $horas ? "de {$horas} horas lectivas" : '';
    return [
        [
            'q' => '¿Este curso es bonificable para mi empresa?',
            'a' => "Sí, el curso {$nc} {$duracion} es 100% bonificable a través de FUNDAE. Su empresa puede recuperar el coste íntegro. Nos encargamos de toda la gestión sin coste adicional.",
        ],
        [
            'q' => '¿Qué certificado obtendré al completarlo?',
            'a' => 'Al finalizar recibirá un diploma acreditativo oficial con el sello de FUNDAE, que certifica la formación recibida y las horas lectivas completadas. Este diploma tiene reconocimiento institucional y es puntuable en numerosos tribunales de oposiciones y concursos públicos.',
        ],
        [
            'q' => '¿Puedo hacerlo fuera del horario laboral?',
            'a' => 'Sí, la plataforma está disponible las 24 horas del día, los 7 días de la semana. Puede estudiar en horario laboral, fuera de él, o combinando ambos con total flexibilidad. El plazo para completarlo se acuerda con su empresa y habitualmente es de 1 a 3 meses.',
        ],
    ];
}

// ============================================================
// RENDERIZADO HTML DE FAQ
// ============================================================

function render_faq($faqs, $titulo = 'Preguntas frecuentes') {
    if (empty($faqs)) return;
    $t = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    echo '<section class="faq-section">';
    echo '<h2 class="faq-title">' . $t . '</h2>';
    foreach ($faqs as $faq) {
        $q = htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8');
        $a = htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8');
        echo '<details class="faq-item">';
        echo '<summary class="faq-question">' . $q . '</summary>';
        echo '<div class="faq-answer">';
        echo '<p>' . $a . '</p>';
        echo '</div></details>';
    }
    echo '</section>';
}

// ============================================================
// SCHEMA JSON-LD GENERATORS
// ============================================================

function schema_faqpage($faqs) {
    if (empty($faqs)) return '';
    $items = [];
    foreach ($faqs as $faq) {
        $items[] = ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']]];
    }
    $schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function schema_breadcrumbs($items) {
    if (empty($items)) return '';
    $list = [];
    foreach ($items as $i => $item) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['name'], 'item' => $item['url']];
    }
    $schema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function schema_course($nombre, $descripcion, $horas, $url, $imagen = '') {
    $schema = [
        '@context' => 'https://schema.org', '@type' => 'Course',
        'name' => $nombre, 'description' => mb_substr(strip_tags($descripcion), 0, 300),
        'provider' => ['@type' => 'Organization', 'name' => 'Ciberaula', 'url' => 'https://www.ciberaula.org', 'sameAs' => 'https://www.ciberaula.com'],
        'url' => $url, 'inLanguage' => 'es', 'isAccessibleForFree' => false,
        'offers' => ['@type' => 'Offer', 'category' => 'Formación bonificada FUNDAE', 'price' => '0', 'priceCurrency' => 'EUR', 'description' => '100% bonificable para empresas a través de FUNDAE'],
        'hasCourseInstance' => ['@type' => 'CourseInstance', 'courseMode' => 'online', 'courseWorkload' => $horas ? "PT{$horas}H" : 'PT40H'],
    ];
    if ($imagen) $schema['image'] = $imagen;
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function schema_organization() {
    $schema = [
        '@context' => 'https://schema.org', '@type' => 'Organization',
        'name' => 'Ciberaula de Formación Online S.L.', 'alternateName' => 'Ciberaula',
        'url' => 'https://www.ciberaula.org', 'logo' => 'https://www.ciberaula.com/assets/img/logo_opt.png',
        'foundingDate' => '1997', 'description' => 'Empresa de formación online bonificada para empresas. Más de 1.500 cursos 100% bonificables por FUNDAE.',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Paseo de la Castellana 91, 4ª planta', 'addressLocality' => 'Madrid', 'postalCode' => '28046', 'addressCountry' => 'ES'],
        'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => '+34-915-303-387', 'contactType' => 'sales', 'availableLanguage' => 'Spanish'],
        'sameAs' => ['https://www.ciberaula.com', 'https://www.linkedin.com/company/ciberaula'],
    ];
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
