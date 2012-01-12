<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditorController extends Controller
{
    public function getCollection()
    {
        return $this->container->get('server_grove_translation_editor.storage_manager')->getCollection();
    }
    
    protected function getBundles(){
        $data = $this->getCollection()->find();
        $bundles = array();
        foreach($data as $bundle){
            $bundles[] = $bundle['name'];
        }
        return $bundles;
    }
    
    public function saveBundleAction($bundle, $domain){
        $request = $this->container->get('request');
        $data = $this->getCollection()->findOne(array('name' => $bundle));
        $data['domains'][$domain] = $request->get('translations');
        $this->updateData($data);
        return new \Symfony\Component\HttpFoundation\Response('ok');
    }

    public function listAction()
    {
        $default = $this->container->getParameter('locale', 'en');
        
        $bundles = $this->getBundles();
        $bundle = $bundles[0];
        
        return $this->redirect($this->generateUrl('sg_localeditor_editbundle', array('bundle' => $bundle)));
    }
    
    public function editBundleAction($bundle){
        $data = $this->getCollection()->findOne(array('name' => $bundle));
        
        $bundles = $this->getBundles();
        $bundle_name = $bundle;
        $bundle = $data;
        $locales = array();
        
        $default = $this->container->getParameter('locale', 'en');
        
        foreach($bundle['domains'] as $domain){
            $locales = $locales + array_keys($domain);
        }
        
        return $this->render(
                'ServerGroveTranslationEditorBundle:Editor:bundle_list.html.twig', 
                compact('bundles', 'bundle_name', 'bundle', 'locales', 'default') 
        );
    }

    public function removeAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $key = $request->request->get('key');

            $values = $this->getCollection()->find();

            foreach($values as $data) {
                if (isset($data['entries'][$key])) {
                    unset($data['entries'][$key]);
                    $this->updateData($data);
                }
            }

            $res = array(
                'result' => true,
            );
            return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();

        $locales = $request->request->get('locale');
        $key = $request->request->get('key');

        foreach($locales as $locale => $val ) {
            $values = $this->getCollection()->find(array('locale' => $locale));
            $values = iterator_to_array($values);
            if (!count($values)) {
                continue;
            }
            $found = false;
            foreach ($values as $data) {
                if (isset($data['entries'][$key])) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data = array_pop($values);
            }

            $data['entries'][$key] = $val;
            $this->updateData($data);
        }
        if ($request->isXmlHttpRequest()) {
            $res = array(
                'result' => true,
            );
            return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('sg_localeditor_list'));
    }

    public function updateAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $locale = $request->request->get('locale');
            $key = $request->request->get('key');
            $val = $request->request->get('val');

            $values = $this->getCollection()->find(array('locale' => $locale));
            $values = iterator_to_array($values);

            $found = false;
            foreach ($values as $data) {
                if (isset($data['entries'][$key])) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data = array_pop($values);
            }

            $data['entries'][$key] = $val;
            $this->updateData($data);

            $res = array(
                'result' => true,
                'oldata' => $data['entries'][$key],

            );
            return new \Symfony\Component\HttpFoundation\Response(json_encode($res));
        }
    }

    protected function updateData($data)
    {
        $this->getCollection()->update(
            array('_id' => $data['_id'])
            , $data, array('upsert' => true));
    }
}
