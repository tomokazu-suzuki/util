<?php
/**
 * CSV形式の文字列をファイルから1行ずつ取得するためのクラス
 * 
 * ファイルの文字列からエンコーディングを検出して
 * utf-8に文字エンコーディングを変換する
 * 変換した文字列を一時ファイルに書き込んで
 * そのファイルから1行ずつ文字列を取り出す
 * ファイルがテキストであるかバイナリであるか
 * テキストであってもcsv形式であるかなどはこのクラスでは検査してない
 * 
 * @author Tomokazu Suzuki
 */
class FetchCSV
{
  /**
   * コンストラクタ
   */
  public function __construct()
  {
  }

  /**
   * デストラクタ
   */
  public function __destruct()
  {
    $this->closeFile();
  }

  /**
   * 初期処理
   *
   * @param string $file_path csvファイルのパス
   * @return bool 成功時true 失敗時false
   */
  public function init($file_path)
  {
    // fgetcsvがロケール設定を考慮するためutf-8にしておく
    setlocale(LC_ALL, 'ja_JP.UTF-8');

    // csvファイルの内容を文字列で読み込む
    $buffer = file_get_contents($file_path);

    // 文字列のエンコーディングを検出する
    $from_encoding = mb_detect_encoding($buffer, 'ASCII, JIS, UTF-8, CP51932, SJIS-win', true); 

    // 検出できなければfalseを返す
    if(false === $from_encoding)
    {
      return false;
    }

    // 検出したエンコーディングからutf-8に変換
    $buffer = mb_convert_encoding($buffer, 'UTF-8', $from_encoding);

    // 一時ファイル生成してファイルポインタをクラス変数に代入
    $this->setFilePointer(tmpFile());

    // 一時ファイルを生成できなければfalseを返す
    if(false === $this->getFilePointer())
    {
      return false;
    }

    // エンコードした文字列を一時ファイルに書き込み
    // 書き込みに失敗した場合はfalseを返す
    if(false === fwrite($this->getFilePointer(), $buffer))
    {
      $this->closeFile();
      return false;
    }

    // ポインタの位置を最初に戻す
    rewind($this->getFilePointer());

    return true;
  }

  /**
   * ファイルポインタを返す
   *
   * @return resource 
   */
  private function getFilePointer()
  {
    return $this->_file_pointer;
  }

  /**
   * ファイルポインタを代入する
   *
   * @param resource $value
   */
  private function setFilePointer($value)
  {
    $this->_file_pointer = $value;
  }

  /**
   * ファイルから1行分取得しcsvフィールドを処理する
   *
   * @return array|bool 読み込んだ行を含む数値添字配列。空行の場合は空の配列。
   *                    ファイルの終端に達した場合を含めたその他のエラー時はfalse
   * @throws InvalidHandleException 特殊なハンドルを受け取った場合
   */
  public function fetchRow()
  {
    // ファイルから1行分取得
    $row = fgetcsv($this->getFilePointer());

    // falseの場合はファイルを閉じてfalseを返す
    if(false === $row)
    {
      $this->closeFile();
      return false;
    }

    // 無効なハンドルを受け取った場合は例外を投げて終了
    if(null === $row)
    {
      throw new InvalidHandleException();
    }

    // 取得した値が空であれば空の配列を返す
    if(array(null) === $row)
    {
      return array();
    }

    // 取得した値を返す
    return $row;
  }

  /**
   * ファイルを閉じる
   *
   * @return bool ファイルを閉じるのに成功した場合はtrue、失敗した場合はfalse
   */
  public function closeFile()
  {
    return fclose($this->getFilePointer());
  }


  /** @var resource|null ファイルポインタ */
  private $_file_pointer = null;
}

/**
 * ファイルポインタから行を取得する時、無効なハンドルを受け取った場合の例外クラス
 *
 * @author Tomokazu Suzuki
 */
class InvalidHandleException extends Exception
{
}
