<?php
namespace booosta\kubernetes;

use RenokiCo\PhpK8s\KubernetesCluster;

use \booosta\Framework as b;
b::init_module('kubernetes');


class Kubernetes extends \booosta\base\Module
{ 
  use moduletrait_kubernetes;

  protected $url;
  protected $token;
  protected $cluster;
  public $status = 'OK', $error;


  public function get_cluster() { return $this->cluster; }

  public function __construct($url, $token)
  {
    parent::__construct();

    $this->cluster = KubernetesCluster::fromUrl($url);
    $this->cluster->withoutSslChecks();
    $this->cluster->withToken($token);
  }

  public function apply($code)
  {
    try {
      $yaml = $this->cluster->fromYaml($code);
      #b::debug($yaml);
      if(!is_array($yaml)) $yaml = [$yaml];

      foreach($yaml as $y) $y->createOrUpdate();
    } catch(\Exception $e) {
      $this->error = json_encode($e->getPayload(), JSON_PRETTY_PRINT);
      $this->status = 'ERROR';
      return false;
    }

    return true;
  }

  // Some K8s objects cannot be created with the RenokiCo library, so we must use the shell tool (quick & dirty!)
  public function apply_shell($code)
  {
    $filename = md5($code) . '.yaml';
    file_put_contents("../tmp/$filename", $code);
    $result = system("kubectl apply -f ../tmp/$filename 2>&1");
    unlink("../tmp/$filename");

    return $result;
  }

  public function delete($code)
  {
    try {
      $yaml = $this->cluster->fromYaml($code);
      if(!is_array($yaml)) $yaml = [$yaml];

      foreach($yaml as $y):
        $y->update();  // delete() does not work without previous update()
        $y->delete();
      endforeach;
    } catch(\Exception $e) {
      $this->error = $e->getMessage();
      $this->status = 'ERROR';
      return false;
    }

    return true;
  }

  public function getPodByPrefix($name)
  {
    $allpods = $this->cluster->getAllPods();
    $pods = $allpods->filter(function($p) use($name) { return str_starts_with($p->getName(), $name); });
    return $pods->first();
  }
}
