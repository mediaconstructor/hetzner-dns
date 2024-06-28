<?php

namespace HetznerDNS;

class HetznerDNS {

  private $api_token;
  /** @var \CurlHandle|false|null $curl_handle Reusable curl handle */
  private $curl_handle = null;

  public function __construct(array $options){

    $this->api_token = trim($options['api_token']);

  }
  public function __destruct(){
      if (is_null($this->curl_handle)) {
          return;
      }
    curl_close($this->curl_handle);
  }

  private function error($message){

    return array(
      'result' => 'error',
      'message' => $message
    );
  }

  private function curl($method, $url, array $options = null, $body = null){

    curl_setopt($this->curlHandle(), CURLOPT_URL, 'https://dns.hetzner.com/api/v1' . $url);
    curl_setopt($this->curlHandle(), CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($this->curlHandle(), CURLOPT_RETURNTRANSFER, 1);

    $headers = [
      'Auth-API-Token: ' . $this->api_token,
    ];

    if($method == 'POST' || $method == 'PUT'){

      curl_setopt($this->curlHandle(), CURLOPT_POST, 1);

      if(!empty($body)){

        array_push($headers, 'Content-Type: text/plain');
        curl_setopt($this->curlHandle(), CURLOPT_POSTFIELDS, $body);

      } else {

        array_push($headers, 'Content-Type: application/json');
        curl_setopt($this->curlHandle(), CURLOPT_POSTFIELDS, json_encode($options));

      }

    }

    curl_setopt($this->curlHandle(), CURLOPT_HTTPHEADER, $headers);

    $status = curl_getinfo($this->curlHandle(), CURLINFO_HTTP_CODE);
    $response = curl_exec($this->curlHandle());

    switch ($status) {
      case '200':
        break;

      case '400':
        return $this->error('Pagination selectors are mutually exclusive');

      case '401':
        return $this->error('Unauthorized');

      case '403':
        return $this->error('Forbidden');

      case '404':
        return $this->error('Not Found');

      case '406':
        return $this->error('Not acceptable');

      case '422':
        return $this->error('Unprocessable entity');
    }

    return json_decode($response, true); //Respone in array

  }

    /**
     * Get a curl handle
     * @return \CurlHandle|false
     */
  private function curlHandle(){
    if (null === $this->curl_handle) {
      $this->curl_handle = curl_init();
    }

    return $this->curl_handle;
  }

  //Create DNS zone
  public function createZone($options){

    #Required Options: name

    return $this->curl('POST', '/zones', $options);

  }

  //Import DNS zone file
  public function importZoneFile($id, $body){

    #Required Options: zone_id

    return $this->curl('POST', '/zones/' . $id . '/import', [], $body);

  }

  //Export DNS zone file
  public function exportZoneFile($id){

    return $this->curl('GET', '/zones/' . $id . '/export');

  }

  //Get all DNS zones
  public function getZones(array $options = null){

    if($options){
      $query = http_build_query($options);
    } else {
      $query = '';
    }

    return $this->curl('GET', '/zones?' . $query);

  }

  //Get DNS zone
  public function getZone($id){

    return $this->curl('GET', '/zones/' . $id);

  }

  //Delete DNS zone
  public function updateZone($id, $options){

    #Required Options: name, ttl

    return $this->curl('PUT', '/zones/' . $id, $options);

  }

  //Delete DNS zone
  public function deleteZone($id){

    return $this->curl('DELETE', '/zones/' . $id);

  }

  //Create DNS record
  public function createRecord($options){

    #Required Options: name, type, value, zone_id

    return $this->curl('POST', '/records', $options);

  }

  //Get all DNS records
  public function getRecords(array $options = null){

    if($options){
      $query = http_build_query($options);
    } else {
      $query = '';
    }

    return $this->curl('GET', '/records?' . $query);

  }

  //Get a DNS record
  public function getRecord($id){

    return $this->curl('GET', '/records/' . $id);

  }

  //Update DNS record
  public function updateRecord($id, $options){

    #Required Options: name, type, value, zone_id

    return $this->curl('PUT', '/records/' . $id, $options);

  }

  //Delete DNS record
  public function deleteRecord($id){

    return $this->curl('DELETE', '/records/' . $id);

  }

}
