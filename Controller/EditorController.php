<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditorController extends Controller
{
    public function getCollection()
    {
        return $this->container->get('server_grove_translation_editor.storage_manager')->getCollection();
    }

    public function listAction()
    {
        $default = $this->container->getParameter('locale', 'en');
        
        $locales = $this->getCollection()->find(array('locales' => 'locales'));
        $locales = $locales->getNext();
        $locales = $locales['available_locales'];
//        var_dump($locales);
        
        $data = $this->getCollection()->find(array('translations' => 'translations'));
        $translations = array();
        foreach($data as $translation){
            $translations[] = $translation;
        }
        
        return $this->render(
                'ServerGroveTranslationEditorBundle:Editor:list.html.twig', 
                compact('locales', 'translations', 'default') 
        );
//        foreach($translations as $translation){
////            var_dump($translation);
//            $bundle = $translation['bundle'];
//            echo $bundle.'<br>';
//            foreach($translation[$bundle] as $domain){
////                var_dump($domain);
//                echo "-->".$domain['name'].'<br>';
//                foreach($domain['locales'] as $locale => $entries){
//                    echo "----->".$locale.'<br>';
//                    print_r($entries['entries']);
//                    echo "<br>";
//                }
//            }
//        }
        
        return compact('locales', 'translations');

//        $locales = array();
//        $files = array();
//
//        $default = $this->container->getParameter('locale', 'en');
//        $missing = array();
//
//        
//        foreach ($data as $d) {
//            $files[$d['file_canonical']][$d['locale']] = $d;
//        }
//        foreach($files as $name => $locales){
//            var_dump(array_keys($locales));
////            sort(array_flip($locales));
//        }
////        foreach($files as $fname => $locales){
////            echo $fname.'<br>';
////            foreach($locales as $locale => $entries){
////                echo '&nbsp;&nbsp;'.$locale.': '.count($entries).'<br>';
//////                echo print_r($entries, true);
////            }
////        }
//        
//        foreach ($data as $d) {
//            $locales[$d['locale']] = $d;
//        }
//
//        $keys = array_keys($locales);
//
//        foreach ($keys as $locale) {
//            if ($locale != $default) {
//                foreach ($locales[$default]['entries'] as $key => $val) {
//                    if (!isset($locales[$locale]['entries'][$key]) || $locales[$locale]['entries'][$key] == $key) {
//                        $missing[$key] = 1;
//                    }
//                }
//            }
//        }
//        $locales = array();
//        $files = array();
//
//        $default = $this->container->getParameter('locale', 'en');
//        $missing = array();
//
//        
//        foreach ($data as $d) {
//            $files[$d['file_canonical']][$d['locale']] = $d;
//        }
//        foreach($files as $name => $locales){
//            var_dump(array_keys($locales));
////            sort(array_flip($locales));
//        }
////        foreach($files as $fname => $locales){
////            echo $fname.'<br>';
////            foreach($locales as $locale => $entries){
////                echo '&nbsp;&nbsp;'.$locale.': '.count($entries).'<br>';
//////                echo print_r($entries, true);
////            }
////        }
//        
//        foreach ($data as $d) {
//            $locales[$d['locale']] = $d;
//        }
//
//        $keys = array_keys($locales);
//
//        foreach ($keys as $locale) {
//            if ($locale != $default) {
//                foreach ($locales[$default]['entries'] as $key => $val) {
//                    if (!isset($locales[$locale]['entries'][$key]) || $locales[$locale]['entries'][$key] == $key) {
//                        $missing[$key] = 1;
//                    }
//                }
//            }
//        }
//
//        return $this->render('ServerGroveTranslationEditorBundle:Editor:list.html.twig', array(
//                'locales' => $locales,
//                'default' => $default,
//                'missing' => $missing,
//                'files'   => $files,
//            )
//        );
//        return new \Symfony\Component\HttpFoundation\Response('hoho');
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
