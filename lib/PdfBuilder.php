<?php
// lib/PdfBuilder.php — Versión robusta con logos opcionales.

require_once __DIR__ . '/../config.php';

class PdfBuilder
{
    /**
     * @param array $entries Arreglo de gráficos, cada uno con ['title' => '...', 'png' => '/ruta/absoluta/al/grafico.png']
     * @param string $outfile Ruta absoluta donde se guardará el PDF.
     * @param string $engine 'dompdf' o 'wkhtmltopdf'.
     * @throws RuntimeException Si ocurren errores críticos durante la generación.
     */
    public static function build(array $entries, string $outfile, $engine = 'dompdf'): void
    {
        if (empty($entries)) {
            throw new RuntimeException('No hay graficos para generar el PDF.');
        }

        // 1. Prepara las imágenes de los gráficos
        $imgs = [];
        foreach ($entries as $i => $e) {
            $p = isset($e['png']) ? (string)$e['png'] : '';
            if ($p === '' || !is_file($p)) {
                // En lugar de fallar, podríamos registrar un aviso y continuar, pero por ahora mantenemos el error.
                throw new RuntimeException('PNG faltante para la entrada #'.$i);
            }
            $bin = @file_get_contents($p);
            if ($bin === false || strlen($bin) < 100) {
                throw new RuntimeException('PNG ilegible o vacio en #'.$i);
            }
            $imgs[] = [
                'title' => (string)($e['title'] ?? ''),
                'b64'   => 'data:image/png;base64,'.base64_encode($bin),
            ];
        }

        // ==================== INICIO DE LA CORRECCIÓN DE LOGOS ====================

        // 2. Prepara el logo personalizado (opcional)
        $custom_logo_b64 = ''; // Por defecto, vacío
        $logo_path_relative = defined('CUSTOM_LOGO_PATH') ? CUSTOM_LOGO_PATH : 'assets/sonda.png';
        $logo_path_absolute = __DIR__ . '/../' . $logo_path_relative;
        if (is_file($logo_path_absolute)) {
            $custom_logo_b64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path_absolute));
        }

        // 3. Prepara el logo de Zabbix (opcional)
        $zabbix_logo_b64 = ''; // Por defecto, vacío
        $zabbix_logo_path = __DIR__ . '/../assets/Zabbix_logo.png';
        if (is_file($zabbix_logo_path)) {
            $zabbix_logo_b64 = 'data:image/png;base64,' . base64_encode(file_get_contents($zabbix_logo_path));
        }

        // ===================== FIN DE LA CORRECCIÓN DE LOGOS ======================

        // 4. Construye el HTML
        $html = self::buildHtml($imgs, $custom_logo_b64, $zabbix_logo_b64);

        // 5. Genera el PDF con el motor seleccionado
        $eng = $engine ?: 'dompdf';
        if ($eng === 'wkhtmltopdf') {
            self::buildWithWkhtml($html, $outfile);
        } else {
            self::buildWithDompdf($html, $outfile);
        }

        if (!is_file($outfile) || filesize($outfile) < 1000) {
            throw new RuntimeException('PDF vacio o no generado.');
        }
    }

    private static function buildHtml(array $imgs, string $customLogoB64, string $zabbixLogoB64): string
    {
        $blocks = [];
        $toc = [];
        $n = 1;
        
        foreach ($imgs as $img) {
            $id = 'g'.$n++;
            $t = htmlspecialchars($img['title'], ENT_QUOTES, 'UTF-8');
            $toc[] = ['id' => $id, 'title' => $t];
            $blocks[] = [
                'id' => $id,
                'title' => $t,
                'content' => '<div class="chart-block">'.
                           '<h2 id="'.$id.'" class="chart-title">'.$t.'</h2>'.
                           '<div class="chart-container">'.
                           '<img src="'.$img['b64'].'" class="chart-image" />'.
                           '</div></div>'
            ];
        }

        $tocHtml = '<div class="toc-container">'.
                  '<h2 class="toc-title">' . t('pdf_toc_title') . '</h2>'.
                  '<table class="toc-table"><tbody>';

        foreach ($toc as $entry) {
            $target = '#'.$entry['id'];
            $tocHtml .= '<tr class="toc-row">'
                       .'<td class="toc-title-cell"><a href="'.$target.'" class="toc-link">'.$entry['title'].'</a></td>'
                       .'<td class="toc-dots-cell"><span class="dots"></span></td>'
                       .'<td class="toc-page-cell"><span class="toc-page" data-target="'.$target.'"></span></td>'
                       .'</tr>';
        }
        $tocHtml .= '</tbody></table></div>';

        $content = '';
        foreach ($blocks as $block) {
            $content .= $block['content'];
        }
        
        // --- CÓDIGO HTML MODIFICADO PARA LOGOS OPCIONALES ---
        $customLogoHtml = $customLogoB64 ? '<img src="'.$customLogoB64.'" alt="Logo">' : '';
        $zabbixLogoHtml = $zabbixLogoB64 ? '<img src="'.$zabbixLogoB64.'" alt="Zabbix Logo">' : '';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>' . t('pdf_main_title') . '</title>
            <style>
                @page { margin: 80px 50px 60px 50px; }
                body { font-family: Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; margin: 0; padding: 0; }
                .header { position: fixed; top: -60px; left: 0; right: 0; height: 60px; padding: 10px 50px; display: flex; align-items: center; border-bottom: 1px solid #ddd; background: white; }
                .header img { height: 40px; }
                .footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; display: flex; justify-content: center; align-items: center; gap: 10px; background: white; padding: 5px 0; }
                .footer img { height: 20px; }
                .footer .separator { color: #ccc; }
                .chart-block { margin: 0 0 20px 0; page-break-inside: avoid; padding: 10px 0 20px 0; border-bottom: 1px solid #f0f0f0; }
                .toc-container { margin-bottom: 30px; }
                .toc-title { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 5px; margin-bottom: 15px; }
                .toc-table { width: 100%; border-collapse: collapse; table-layout: auto; }
                .toc-table td { padding: 4px 0; }
                .toc-row { height: auto; line-height: 1; vertical-align: middle; }
                .toc-title-cell { width: auto; padding: 2px 6px 2px 0; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-dots-cell { width: 100%; overflow: hidden; }
                .toc-dots-cell .dots { display: block; height: 0; margin: 0 4px; border-bottom: 1px dotted #9ab0be; }
                .toc-page-cell { width: 40px; text-align: right; padding-left: 6px; white-space: nowrap; }
                .toc-link { color: #2c3e50; text-decoration: none; display: inline-block; max-width: 100%; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-link:hover { color: #1a5276; text-decoration: underline; }
                .toc-page { color: #2c3e50; font-size: 0.9em; text-align: right; }
                .toc-page:after { content: target-counter(attr(data-target), page); }
                .chart-title { color: #1a5276; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; }
                .chart-container { width: 100%; text-align: center; }
                .chart-image { max-width: 100%; height: auto; margin: 0 auto; display: block; }
            </style>
        </head>
        <body>
            <div class="header">' . $customLogoHtml . '</div>
            <div class="content">
                <h1>' . t('pdf_main_title') . '</h1>
                '.$tocHtml.'
                '.$content.'
            </div>
            <div class="footer">
                <span>' . t('pdf_generated_on') . ' '.date('d/m/Y H:i:s').'</span>
                <span class="separator">|</span>
                <span>' . t('pdf_author_credit') . '</span>
                ' . $zabbixLogoHtml . '
            </div>
            <script type="text/php">
                if (isset($pdf)) {
                    $text = "' . t('pdf_page_x_of_y') . '";
                    $font = $fontMetrics->get_font("Arial, sans-serif", "normal");
                    $size = 8;
                    $y = $pdf->get_height() - 20;
                    $x = $pdf->get_width() - 50 - $fontMetrics->get_text_width($text, $font, $size);
                    $pdf->page_text($x, $y, $text, $font, $size);
                }
            </script>
        </body>
        </html>';

        return $html;
    }

    private static function buildWithDompdf(string $html, string $outfile): void
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Dompdf no esta disponible. Instala con: composer require dompdf/dompdf');
        }
        require_once $autoload;

        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outfile, $dompdf->output());
    }

    private static function buildWithWkhtml(string $html, string $outfile): void
    {
        $tmp = (defined('APP_TMP') ? APP_TMP : sys_get_temp_dir()).DIRECTORY_SEPARATOR.'html_'.uniqid().'.html';
        file_put_contents($tmp, $html);
        $cmd = 'wkhtmltopdf --enable-local-file-access --quiet --margin-top 70 --margin-bottom 40 --header-html "about:blank" --footer-html "about:blank" '.escapeshellarg($tmp).' '.escapeshellarg($outfile).' 2>&1';
        exec($cmd, $out, $rc);
        @unlink($tmp);
        if ($rc !== 0) {
            throw new RuntimeException('wkhtmltopdf fallo rc='.$rc);
        }
    }
}