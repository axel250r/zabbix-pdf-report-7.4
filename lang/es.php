<?php
// lang/es.php
return [
    // General
    'theme_dark' => 'Tema Oscuro',
    'theme_light' => 'Tema Claro',
    'error_invalid_session' => 'Sesión inválida',
    'error_server_error' => 'Error en el servidor',

    // login.php
    'login_title' => 'Login Zabbix',
    'login_heading' => 'Iniciar sesión',
    'login_subheading' => 'El front usa tu usuario. La API usa una cuenta de servicio.',
    'login_user_label' => 'Usuario Zabbix',
    'login_pass_label' => 'Contraseña',
    'login_button' => 'Entrar',
    'login_error_invalid_form' => 'Formulario inválido',
    'login_error_invalid_credentials' => 'Credenciales inválidas',
    'login_error_frontend_rejected' => 'El frontend de Zabbix rechazó el login',

    // export.php
    'export_title' => 'Exportar PDF',
    'export_logged_in_as' => 'Iniciaste sesión como',
    'export_hosts_label' => 'Hosts (uno por línea o separados por coma)',
    'export_hosts_placeholder' => 'host1, host2, host3',
    'export_groups_label' => 'Grupos de Hosts (uno por línea o separados por coma)',
    'export_groups_placeholder' => 'grupo1, grupo2, grupo3',
    'export_templates_items_label' => 'Plantillas y Items',
    'export_templates_items_placeholder' => 'Template OS Linux, Template App Nginx...',
    'export_from_label' => 'Desde:',
    'export_to_label' => 'Hasta:',
    'export_last_24h' => 'Últimas 24 horas',
    'export_time_range_note' => 'Si completas Desde/Hasta, se usará rango absoluto. Si no, se usa el relativo.',
    'export_generate_pdf_button' => 'Generar PDF',

    // Modals (JavaScript)
    'modal_select_button' => 'Seleccionar',
    'modal_cancel_button' => 'Cancelar',
    'modal_next_button' => 'Siguiente',
    'modal_back_button' => 'Volver',
    'modal_add_items_button' => 'Añadir Items',
    'modal_loading' => 'Cargando...',
    'modal_no_results' => 'No se encontraron resultados.',
    'modal_error_loading' => 'Error al cargar los datos.',
    'modal_select_hosts_title' => 'Seleccionar Hosts',
    'modal_filter_hosts_placeholder' => 'Filtrar hosts...',
    'modal_select_groups_title' => 'Seleccionar Grupos de Hosts',
    'modal_filter_groups_placeholder' => 'Filtrar grupos...',
    'modal_select_templates_title' => 'Seleccionar Plantillas',
    'modal_filter_templates_placeholder' => 'Filtrar plantillas...',
    'modal_select_items_title' => 'Seleccionar Items',
    'modal_filter_items_placeholder' => 'Filtrar ítems...',
    'alert_select_template' => 'Por favor, selecciona al menos una plantilla.',
    'alert_select_item' => 'Por favor, selecciona al menos un ítem.',

    // generate.php
    'generate_invalid_input' => 'Debes indicar al menos un host o grupo, y un ítem.',
    'generate_invalid_range' => 'Rango de tiempo inválido.',
    'generate_no_hosts_found' => 'No se encontraron hosts con los datos entregados.',
    'generate_web_login_failed' => 'No fue posible iniciar sesión web en Zabbix para descargar los gráficos.',
    'generate_no_graphs' => 'No se generaron gráficos válidos. Verifica la selección, permisos y rango de tiempo.',
    'generate_pdf_failed' => 'Fallo al generar PDF',

    // get_*.php errors
    'get_hosts_error' => 'No se obtuvieron hosts de la API de Zabbix.',
    'get_groups_error' => 'No se obtuvieron grupos de hosts de la API de Zabbix.',
    'get_items_error' => 'No se obtuvieron ítems de la API de Zabbix.',

    // ... (traducciones existentes)

    // PdfBuilder.php
    'pdf_main_title' => 'Reporte de Gráficos de Zabbix',
    'pdf_toc_title' => 'Índice',
    'pdf_generated_on' => 'Generado el',
    'pdf_page_x_of_y' => 'Página {PAGE_NUM} de {PAGE_COUNT}',

    // ... (traducciones existentes)

    // Créditos de autor
    'common_author_credit' => 'Desarrollado por Axel Del Canto',
    'pdf_author_credit' => 'PDF Desarrollado por Axel Del Canto',

    // ... (traducciones existentes de los modales)
    'modal_add_items_button' => 'Añadir Items',
    'modal_select_page_button' => 'Seleccionar Página', // <-- AÑADIR
    'modal_deselect_page_button' => 'Deseleccionar Página', // <-- AÑADIR
    'modal_loading' => 'Cargando...',

    // ... traducciones existentes ...
    'export_to_excel_button' => 'Exportar a Excel',
    'excel_export_title' => 'Exportar Datos a Excel',
    'excel_export_report_type' => 'Tipo de Reporte',
    'excel_export_host_list' => 'Lista General de Hosts',
    'excel_export_inventory' => 'Inventario Detallado de Hosts',
    'excel_export_generate_button' => 'Generar Excel',

    // ... boton de rangos

    'export_last_month' => 'Último Mes',
    'export_last_6_months' => 'Últimos 6 Meses',
    'export_from_label' => 'Desde:',
    'export_to_label' => 'Hasta:',
    'export_last_24h' => 'Últimas 24 horas',
];