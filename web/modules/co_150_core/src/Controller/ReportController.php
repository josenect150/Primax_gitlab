<?php

namespace Drupal\co_150_core\Controller;

use Drupal\Core\Database\Database;
use Drupal\co_150_core\Core\XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ReportController class manager.
 */
class ReportController {

  /**
   * Excel.
   */
  public function export($table) {
    $this->table = $table;
    [$header, $rows] = $this->getData(FALSE);
    $filename = 'export_' . $this->table . '_' . date('Y_m_d_H_i_s') . '.csv';
    // print(json_encode($rows));die;
    $temp_file = $this->writeAndDownloadXlsx($header, $rows);
    $headers = [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => 'attachment; filename=' . XlsxWriter::sanitizeFilename($filename),
    ];
    // var_dump($headers);die;
    return new BinaryFileResponse($temp_file, 200, $headers, FALSE);
  }

  /**
   * WriteAndDownloadXlsx.
   */
  private function writeAndDownloadXlsx($header, $rows) {
    $header_types = [];
    foreach ($header as $key => $header_item) {
      $header_types[$header_item] = 'string';
    }
    $writer = new XlsxWriter();
    $writer->setAuthor('150Porciento');
    $writer->writeSheet($rows, 'Hoja 1', $header_types);
    // Create a temp file and write.
    $temp_dir = sys_get_temp_dir();
    $temp_file = tempnam($temp_dir, "150porciento_xlsx_writer_");
    $writer->writeToFile($temp_file);
    return $temp_file;
  }

  /**
   * Get data From DB.
   */
  private function getData($excel = FALSE) {
    $con = Database::getConnection();
    $fieldsDB = $con->query("DESCRIBE `{$this->table}`")->fetchAll();
    $fields = [];
    foreach ($fieldsDB as $key => $value) {
      $fields[] = $value->Field;
    }
    $fields[] = 'options';

    $idPrincipal = array_search('id', $fields) === FALSE ? $fields[0] : 'id';

    $query = \Drupal::database()->select($this->table, 'r');
    $result = $query->fields('r')->orderBy($idPrincipal, 'DESC')->execute();
    $rows = $header = [];
    foreach ($result as $item) {
      if (!$header) {
        $header = array_keys((array) $item);
      }
      $row = (array) $item;
      if (isset($row['created_at'])) {
        if (is_numeric($row['created_at'])) {
          $row['created_at'] = date('r', $row['created_at']);
        }
      }
      $rows[] = $row;
    }
    return [$header, $rows];
  }

}
