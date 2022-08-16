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


  public function __construct($url, $token)
  {
    parent::__construct();

    $this->cluster = KubernetesCluster::fromUrl($url);
    $this->cluster->withoutSslChecks();

    $this->cluster->withToken($token);
  }

  public function apply($code)
  {
    $yaml = $cluster->fromYaml($code);
    return $yaml->createOrUpdate();
  }

  public function delete($code)
  {
    $yaml = $cluster->fromYaml($code);
    return $yaml->delete();
  }
}
