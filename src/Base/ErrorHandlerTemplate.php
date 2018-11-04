<style>
  .codeError {
    padding: 10px 10px;
    background-color: white;
    position: absolute;
    float: left;
    width: 90%;
	z-index: 10000 !important;
  }

  .codeError table {
    border: 0;
    margin: 0;
    padding: 0;
  }

  .codeError td {
    font-size: 0.65em !important;
    font-family: Verdana !important;
    text-align: left;
    vertical-align: top;
    white-space: nowrap;
    margin: 0;
    padding: 0;
  }

  .codeError .stack {
    border: 0;
    margin: 0;
    padding: 0;
  }

  .codeError .stack td {
    color: #006600;
    border: 0;
    margin: 0;
    padding: 0 15px 0 0;
    vertical-align: top;
  }

  .codeError .file td {
    color: #808080;
  }

  .codeError .file .current {
    color: crimson;
  }

  .codeError pre {
    font-size: 12px;
  }

  .codeError .dberror {
    font-family: 'Courier New';
    font-size: 110%;
    color: crimson;
    vertical-align: top;
    margin: 0;
    padding: 0;
  }
</style>

<div class='codeError'>
  <table style='position: static !important; float: none !important;'>
    <tr>
      <td><strong>error level: </strong>&nbsp;</td>
      <td><?=$data['error_level']?></td>
    </tr>
    <tr>
      <td><strong>error in file: </strong>&nbsp;</td>
      <td><?=$data['error_file']?></td>
    </tr>
    <tr>
      <td><strong>error in file: </strong>&nbsp;</td>
      <td><?=$data['error_line']?></td>
    </tr>
    <tr>
      <td><strong>error string: </strong>&nbsp;</td>
      <td><span class='dberror'><?=$data['error_string']?></span></td>
    </tr>
      <?php
      if($options['ShowErrorStatement']) {
          ?>
        <tr>
          <td><strong>faled query: </strong>&nbsp;</td>
          <td>
            <table class='file'>
              <tr>
                <td><strong>......................................</strong></td>
              </tr>
              <tr>
                <td>
                  <pre><?=SqlFormatter::format($data['error_statement'], true)?></pre>
                </td>
              </tr>
              <tr>
                <td><strong>......................................</strong></td>
              </tr>
            </table>
          </td>
        </tr>
          <?php
      }
      ?>
    <tr>
      <td><strong>code stack: </strong>&nbsp;</td>
      <td>
        <table class='stack' cellpadding='0' cellspacing='0'>
            <?php
            foreach($data['stack'] as $stack) {
                ?>
              <tr>
                <td valign='top'><?=$stack['file']?></td>
                <td><?=$stack['line']?></td>
                <td><?=$stack['function']?></td>
              </tr>
                <?php
            }
            ?>
        </table>
      </td>
    </tr>
    <tr>
      <td><strong>error_url: </strong>&nbsp;</td>
      <td><?=urldecode($data['error_url'])?></td>
    </tr>
    <tr>
      <td><strong>referer: </strong>&nbsp;</td>
      <td><?=urldecode($data['referer'])?></td>
    </tr>
  </table>
  <hr size=1>
</div>