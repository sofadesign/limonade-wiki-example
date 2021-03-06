<?php

require_once 'lib/limonade.php';



function configure()
{
  $localhost = preg_match('/^localhost(\:\d+)?/', $_SERVER['HTTP_HOST']);
  $env =  $localhost ? ENV_DEVELOPMENT : ENV_PRODUCTION;
  option('env', $env);
  option('pages_dir', file_path(option('root_dir'), 'pages'));
}

function before()
{
  layout('default_layout.php');
  if($page_name = params('page'))
  {
    params('page', rawurldecode($page_name));
  }
}


dispatch('/', 'wikir_home');
  function wikir_home()
  {
    redirect_to('Home');
  }


dispatch('/:page', 'wikir_page_show');
  function wikir_page_show()
  {
    $page_name = params('page');
    if($page = WikirPage::find($page_name))
    {
      lemon_csrf_unset_token();
      set('page_name', $page->name());
      set('page_content', $page->content());
      return html('show.php');
    }
    # If page doesn't exist, redirect to new page form
    redirect_to($page_name, 'new');
  }
  
dispatch('/:page/new', 'wikir_page_new');
  function wikir_page_new()
  {
    $page_name = params('page');
    if(empty($page_name)) halt('A page name must be provided !');
    set('page_name', $page_name);
    return html('new.php');
  }

dispatch_post('/:page', 'wikir_page_create');
  function wikir_page_create()
  {
    if(lemon_csrf_require_valid_token())
    {
      $page_name    = $_POST['page_name'];
      $page_content = $_POST['page_content'];
      if(is_spam($page_content)) return halt(HTTP_FORBIDDEN);
      
      $page = new WikirPage();
      $page->name($page_name);
      $page->content($page_content); 
      if($page->save())
      {
        redirect_to($page->name());
      }
      halt('An error occured. Unable to create this page. Please check page/ dir is writable.');
    }
  }
  
dispatch('/:page/edit', 'wikir_page_edit');
  function wikir_page_edit()
  {
    $page_name = params('page');
    if($page = WikirPage::find($page_name))
    {
      lemon_csrf_unset_token();
      set('page_name', $page->name());
      set('page_content', $page->content());
      return html('edit.php');
    }
    halt(NOT_FOUND);
  }

dispatch_put('/:page', 'wikir_page_update');
  function wikir_page_update()
  {
    $page_name    = params('page');
    $page_content = $_POST['page_content'];
    if(is_spam($page_content)) return halt(HTTP_FORBIDDEN);
    
    if($page = WikirPage::find($page_name))
    {
      if(lemon_csrf_require_valid_token())
      {
        $page->name($page_name);
        $page->content($page_content);
        if($page->save() !== FALSE)
        {
          redirect($page->name());
        }
        halt('An error occured. Unable to update this page. Please check page/ dir is writable.');
      }
    }
    halt(NOT_FOUND);
  }
  
dispatch_delete('/:page', 'wikir_page_destroy');
  function wikir_page_destroy()
  {
    $page_name = params('page');
    if($page_name == 'Home') halt("Home page can't be deleted.");
    if($page = WikirPage::find($page_name))
    {
      $page->name($page_name);
      $page->content($page_content);
      if($page->destroy())
      {
        redirect('/');
      }
      halt('An error occured. Unable to destroy this page. Please check page/ dir is writable.');
    }
    halt(NOT_FOUND);
  }
  
run();

?>