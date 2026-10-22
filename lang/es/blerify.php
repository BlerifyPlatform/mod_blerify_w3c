<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Module metadata.
$string['modulename'] = 'Certificado Blerify';
$string['modulenameplural'] = 'Certificados Blerify';
$string['modulename_help'] = 'El módulo de actividad Certificado Blerify permite emitir Credenciales Verificables W3C a los estudiantes a través de la plataforma Blerify.';
$string['pluginadministration'] = 'Administración de Certificado Blerify';
$string['pluginname'] = 'Certificado Blerify';

// Admin settings.
$string['setting_service_account_json'] = 'JSON de cuenta de servicio';
$string['setting_service_account_json_desc'] = 'Suba el archivo JSON de su cuenta de servicio Blerify (ej. service-account-SA2-sa-XXXXXXX.json). Este archivo contiene todas las credenciales necesarias: client_id, organization_id, private_key, token_uri e iam_audience.';
$string['setting_service_account_upload_help'] = 'Seleccione un archivo .json de cuenta de servicio para importar.';
$string['setting_service_account_current'] = 'Cuenta de servicio actual (solo lectura):';
$string['setting_service_account_invalid_json'] = 'El archivo subido no contiene JSON válido.';

// Activity form.
$string['blerifysettings'] = 'Configuración de Blerify';
$string['projectid'] = 'ID de Proyecto';
$string['projectid_help'] = 'UUID del proyecto Blerify para este certificado';
$string['templateid'] = 'ID de Plantilla';
$string['templateid_help'] = 'UUID de la plantilla de credencial Blerify';
$string['completionissue'] = 'Emitir al completar el curso';
$string['completionissue_help'] = 'Emitir automáticamente un certificado cuando el estudiante complete el curso';

// Validation errors.
$string['error_projectid_required'] = 'El ID de Proyecto es obligatorio';

// View page.
$string['viewheader'] = 'Certificados para: {$a}';
$string['nocertificates'] = 'No hay actividades de certificado Blerify en este curso.';
$string['indexheader'] = 'Certificados Blerify en: {$a}';

// Student view.
$string['certificate_processing'] = 'Estamos generando tu certificado. Esta página se actualizará sola en unos segundos.';
$string['certificate_error'] = 'Hubo un error al emitir tu certificado. Por favor, contacta a tu instructor.';

// Teacher view.
$string['col_student'] = 'Estudiante';
$string['col_email'] = 'Correo electrónico';
$string['col_status'] = 'Estado';
$string['col_credentialid'] = 'ID de Credencial';
$string['col_date'] = 'Fecha de Emisión';
$string['no_credentials_issued'] = 'Aún no se han emitido credenciales.';

// Statuses.
$string['status_pending'] = 'Pendiente';
$string['status_error'] = 'Error';

// Events.
$string['eventcertificatecreated'] = 'Certificado Blerify creado';

// Privacy.
$string['privacy:metadata:blerify_credentials'] = 'Información sobre las credenciales digitales Blerify emitidas a los usuarios';
$string['privacy:metadata:blerify_credentials:userid'] = 'El ID del usuario receptor de la credencial';
$string['privacy:metadata:blerify_credentials:credentialid'] = 'El identificador externo de la credencial en Blerify';
$string['privacy:metadata:blerify_credentials:status'] = 'El estado actual de la credencial';

// Errors.
$string['error_not_configured'] = 'El plugin Blerify no está configurado correctamente. Por favor, contacta al administrador.';
$string['error_api_call'] = 'Error al comunicarse con la API de Blerify: {$a}';

// Manual issuance.
$string['issue_credentials'] = 'Habilitar Credenciales';
$string['issue_credentials_header'] = 'Participantes Inscritos';
$string['issue_credentials_help'] = 'Selecciona los participantes a quienes deseas habilitar una credencial digital para que puedan reclamarla. Solo se muestran participantes sin credencial existente.';
$string['issue_selected'] = 'Habilitar a Seleccionados';
$string['no_participants_available'] = 'Todos los participantes inscritos ya tienen una credencial, o no hay participantes inscritos en este curso.';
$string['select_all'] = 'Seleccionar todos';
$string['deselect_all'] = 'Deseleccionar todos';
$string['issue_success'] = '{$a} credencial(es) en cola de emision. La tabla muestra el estado de cada una.';
$string['issue_error_partial'] = '{$a->success} credencial(es) habilitada(s). {$a->fail} fallaron.';
$string['issue_no_selection'] = 'No se seleccionaron participantes.';
$string['col_participant'] = 'Participante';
$string['col_actions'] = 'Acciones';
$string['retry_issue'] = 'Reintentar';
$string['retry_success'] = 'Credencial rehabilitada exitosamente.';
$string['retry_failed'] = 'Reintento fallido. Revisa los registros para más detalles.';

// Course completion notification.

// Capabilities.
$string['blerify:addinstance'] = 'Agregar una nueva actividad de Certificado Blerify';
$string['blerify:view'] = 'Ver Certificado Blerify';
$string['blerify:manage'] = 'Gestionar Certificados Blerify';

// Security.
$string['usernotenrolled'] = 'El usuario especificado no está inscrito en este curso.';

// Configuracion del certificado en el formulario de la actividad.
$string['certificatename'] = 'Nombre del certificado';
$string['certificatename_help'] = 'El nombre que los estudiantes veran para este certificado en el curso.';
$string['templatetoissue'] = 'Plantilla a emitir';
$string['templatetoissue_help'] = 'La plantilla de credencial de Blerify con la que se emite este certificado. La lista proviene del proyecto configurado para este curso.';
$string['passgrade'] = 'Nota minima para emitir (%)';
$string['passgrade_help'] = 'La nota mínima requerida cuando Moodle marca el curso como finalizado.';

// Vista del estudiante.
$string['certificate_not_yet'] = 'Tu certificado aun no esta disponible.';
$string['certificate_threshold'] = 'Necesitas una nota del curso de al menos';
$string['certificate_current_grade'] = 'Tu nota actual:';
$string['pdf_download'] = 'Descargar PDF';
$string['pdf_open'] = 'Abrir el PDF';

// Estados de la credencial.
$string['status_issuing'] = 'Emitiendo';
$string['status_issued'] = 'Emitida';
$string['status_claimed'] = 'Reclamada';
$string['error_detail'] = 'Detalle del error';

// Errores.
$string['error_no_templates'] = 'El proyecto configurado para este curso no tiene plantillas de credencial disponibles.';
$string['error_templates_unavailable'] = 'No se pudieron cargar las plantillas de credencial. Revisa la configuracion de la cuenta de servicio de Blerify.';
$string['error_passgrade_range'] = 'La nota minima debe estar entre 0 y 100.';
$string['error_credential_not_ready'] = 'Esta credencial aun no esta disponible.';
$string['certificate_in_wallet'] = 'El certificado no esta disponible en Moodle. Esta credencial ya esta en tu wallet; si necesitas el PDF aqui, pide a tu docente que la vuelva a emitir.';

$string['privacy:metadata:blerify_credentials:code'] = 'El codigo de reclamo usado para construir el enlace de la billetera';
$string['privacy:metadata:blerify_credentials:timecreated'] = 'La fecha en que se creo el registro de la credencial';
$string['privacy:metadata:blerify_api'] = 'Datos enviados a la plataforma Blerify para emitir una credencial';
$string['privacy:metadata:blerify_api:email'] = 'El correo al que se emite la credencial';
$string['privacy:metadata:blerify_api:fullname'] = 'El nombre que se imprime en la credencial';

$string['error_no_project_id'] = 'Esta actividad no tiene ningun proyecto de Blerify seleccionado. Edita sus ajustes y elige uno.';

$string['projecttoissue'] = 'Proyecto';
$string['projecttoissue_help'] = 'Proyecto de Blerify bajo el que se emite este certificado. Solo aparecen los proyectos en los que la cuenta de servicio puede emitir credenciales. Las plantillas de abajo pertenecen al proyecto que elijas aqui.';
$string['templatepreview'] = 'Vista previa';
$string['templatepreview_none'] = 'Esta plantilla no tiene imagen de vista previa en Blerify.';
$string['error_no_projects'] = 'La cuenta de servicio no tiene ningun proyecto con permiso de emision. Revisa la configuracion en Blerify.';

$string['error_invalid_project'] = 'El proyecto seleccionado no esta disponible para esta cuenta de servicio.';
$string['error_invalid_template'] = 'La plantilla seleccionada no pertenece al proyecto elegido.';

$string['privacy:metadata:blerify_credentials:projectid'] = 'El proyecto de Blerify con el que se emitio la credencial';
$string['privacy:metadata:blerify_credentials:templateid'] = 'La plantilla con la que se emitio la credencial';
$string['privacy:metadata:blerify_credentials:remotestatus'] = 'El ultimo estado informado por la API de Blerify';
$string['privacy:metadata:blerify_credentials:errordetail'] = 'El motivo por el que fallo el ultimo intento de emision';
$string['privacy:metadata:blerify_credentials:timeissued'] = 'La fecha en que se emitio la credencial';
$string['privacy:metadata:blerify_credentials:timeclaimed'] = 'La fecha en que se reclamo la credencial en una billetera';
$string['completion_notification_subject'] = 'Blerify - Tu credencial está lista para reclamar';
$string['completion_notification_body'] = 'Felicitaciones por completar el curso. Tu credencial digital está lista. Visita la actividad de certificado en el curso para reclamarla escaneando el código QR con tu Blerify Wallet.';
$string['error_no_template_id'] = 'Esta actividad no tiene ninguna plantilla de Blerify seleccionada. Edita sus ajustes y elige una.';

$string['setting_service_account_enabled'] = 'Cuenta de servicio activa';
$string['setting_service_account_enabled_desc'] = 'Desmarca para dejar el plugin en pausa sin borrar la cuenta de servicio. Mientras este inactiva no se emite ninguna credencial ni se consulta su estado; el archivo subido se conserva cifrado y vuelve a usarse en cuanto la marques de nuevo.';
$string['setting_service_account_inactive'] = 'Cuenta de servicio cargada pero inactiva';
$string['error_project_not_in_organization'] = 'El proyecto configurado en esta actividad no pertenece a la organizacion de la cuenta de servicio cargada.';
$string['privacy:metadata:core_files'] = 'Copias del PDF y la miniatura del certificado emitido que Moodle conserva.';
$string['task_sync_claims'] = 'Comprobar que credenciales emitidas ya estan en una wallet';
$string['error_reason_unknown'] = 'La emision fallo. Abre el detalle tecnico de abajo o contacta con el administrador del sitio.';
$string['error_reason_project_not_in_organization'] = 'El proyecto configurado en esta actividad no pertenece a la organizacion de la cuenta de servicio activa. Edita la actividad, vuelve a elegir el proyecto y la plantilla, y habilita de nuevo al estudiante.';
$string['error_reason_credential_not_found'] = 'Blerify no encuentra esta credencial. Puede que se haya eliminado en el portal. Vuelve a emitirla.';
$string['error_reason_polling_failed'] = 'Moodle no pudo contactar con Blerify para completar la emision. Revisa la conexion del servidor y vuelve a intentarlo.';
$string['error_reason_polling_timeout'] = 'Blerify no termino de generar la credencial a tiempo. Vuelve a intentarlo y avisa a Blerify si se repite.';
$string['error_reason_assets_not_stored'] = 'La credencial se emitio, pero Moodle no pudo guardar una copia del certificado. Comprueba que el servidor tenga salida a internet y vuelve a intentarlo.';
$string['warn_project_mismatch'] = 'Esta actividad apunta a un proyecto de Blerify que no pertenece a la cuenta de servicio activa. No se podra emitir ninguna credencial hasta que edites la actividad y vuelvas a elegir el proyecto.';
$string['warn_edit_activity'] = 'Editar la actividad';
$string['check_title'] = 'Blerify: revision de la configuracion de las actividades';
$string['check_intro'] = 'Todas las actividades Blerify del sitio, comprobadas contra los proyectos y plantillas que puede usar la cuenta de servicio activa (organizacion {$a}).';
$string['check_account_disabled'] = 'La cuenta de servicio de Blerify esta desactivada, no se puede comprobar nada.';
$string['check_api_unreachable'] = 'No se pudo contactar con Blerify, no se puede comprobar nada.';
$string['check_none'] = 'Todavia no hay actividades Blerify en este sitio.';
$string['check_ok'] = 'Correcta';
$string['check_bad_project'] = 'El proyecto no pertenece a esta organizacion';
$string['check_bad_template'] = 'La plantilla no esta en este proyecto';
$string['check_not_configured'] = 'Sin proyecto o plantilla elegidos';
$string['check_broken'] = 'Hay {$a} actividades que necesitan atencion. No podran emitir credenciales hasta que se vuelva a elegir su proyecto y su plantilla.';
$string['check_all_ok'] = 'Todas las actividades apuntan a un proyecto y una plantilla que esta cuenta de servicio puede usar.';
$string['check_col_course'] = 'Curso';
$string['check_col_activity'] = 'Actividad';
$string['check_col_project'] = 'Proyecto';
$string['check_col_template'] = 'Plantilla';
$string['check_col_state'] = 'Estado';
$string['check_fix'] = 'Corregir';
$string['setting_check_link'] = 'Revisar la configuracion de las actividades';
$string['setting_check_link_desc'] = 'Lista todas las actividades Blerify e indica si su proyecto y su plantilla siguen perteneciendo a la cuenta de servicio cargada. Pasala despues de sustituir la cuenta de servicio.';
$string['warn_stored_project_invalid'] = 'Esta actividad se configuro con el proyecto {$a}, que no pertenece a la organizacion de la cuenta de servicio cargada ahora. Se han vaciado el proyecto y la plantilla: vuelve a elegirlos y guarda.';
$string['warn_stored_template_invalid'] = 'Esta actividad se configuro con la plantilla {$a}, que ya no esta en este proyecto. Se ha vaciado la plantilla: vuelve a elegirla y guarda.';
$string['certificate_not_configured'] = 'Esta actividad no esta configurada, por favor contacta con el administrador.';
