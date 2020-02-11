<?php
include_once('grid_clientes_session.php');
session_start();
$_SESSION['scriptcase']['grid_clientes']['glo_nm_path_imag_temp']  = "";
//check tmp
if(empty($_SESSION['scriptcase']['grid_clientes']['glo_nm_path_imag_temp']))
{
    $str_path_apl_url = $_SERVER['PHP_SELF'];
    $str_path_apl_url = str_replace("\\", '/', $str_path_apl_url);
    $str_path_apl_url = substr($str_path_apl_url, 0, strrpos($str_path_apl_url, "/"));
    $str_path_apl_url = substr($str_path_apl_url, 0, strrpos($str_path_apl_url, "/")+1);
    /*check tmp*/$_SESSION['scriptcase']['grid_clientes']['glo_nm_path_imag_temp'] = $str_path_apl_url . "_lib/tmp";
}
if (!isset($_SESSION['sc_session']))
{
    $NM_dir_atual = getcwd();
    if (empty($NM_dir_atual))
    {
        $str_path_sys  = (isset($_SERVER['SCRIPT_FILENAME'])) ? $_SERVER['SCRIPT_FILENAME'] : $_SERVER['ORIG_PATH_TRANSLATED'];
        $str_path_sys  = str_replace("\\", '/', $str_path_sys);
    }
    else
    {
        $sc_nm_arquivo = explode("/", $_SERVER['PHP_SELF']);
        $str_path_sys  = str_replace("\\", "/", getcwd()) . "/" . $sc_nm_arquivo[count($sc_nm_arquivo)-1];
    }
    $str_path_web    = $_SERVER['PHP_SELF'];
    $str_path_web    = str_replace("\\", '/', $str_path_web);
    $str_path_web    = str_replace('//', '/', $str_path_web);
    $root            = substr($str_path_sys, 0, -1 * strlen($str_path_web));
    if (is_file($root . $_SESSION['scriptcase']['grid_clientes']['glo_nm_path_imag_temp'] . "/sc_apl_default_pasaporte_beta2.txt"))
    {
?>
        <script language="javascript">
         parent.nm_move();
        </script>
<?php
        exit;
    }
}
if (!function_exists("NM_is_utf8"))
{
    include_once("../_lib/lib/php/nm_utf8.php");
}
if (!class_exists('Services_JSON'))
{
    include_once("grid_clientes_json.php");
}
$Save_Grid = new grid_clientes_Save_Grid(); 
$Save_Grid->Save_Grid_init();

class grid_clientes_Save_Grid
{
    function Save_Grid_init()
    {
       global $_POST, $_GET;
       $this->proc_ajax = false;
       if (isset($_POST['script_case_init']))
       {
           $this->sc_init      = filter_input(INPUT_POST, 'script_case_init', FILTER_SANITIZE_NUMBER_INT);
           $this->path_img     = filter_input(INPUT_POST, 'path_img', FILTER_SANITIZE_STRING);
           $this->path_btn     = filter_input(INPUT_POST, 'path_btn', FILTER_SANITIZE_STRING);
           $this->session      = filter_input(INPUT_POST, 'script_case_session', FILTER_SANITIZE_STRING);
           $this->embbed       = isset($_POST['embbed_groupby']) && 'Y' == $_POST['embbed_groupby'];
           $this->tbar_pos     = filter_input(INPUT_POST, 'toolbar_pos', FILTER_SANITIZE_SPECIAL_CHARS);
           $this->sc_origem    = filter_input(INPUT_POST, 'script_origem', FILTER_SANITIZE_STRING);
       }
       elseif (isset($_GET['script_case_init']))
       {
           $this->sc_init      = filter_input(INPUT_GET, 'script_case_init', FILTER_SANITIZE_NUMBER_INT);
           $this->path_img     = filter_input(INPUT_GET, 'path_img', FILTER_SANITIZE_STRING);
           $this->path_btn     = filter_input(INPUT_GET, 'path_btn', FILTER_SANITIZE_STRING);
           $this->session      = filter_input(INPUT_GET, 'script_case_session', FILTER_SANITIZE_STRING);
           $this->embbed       = isset($_GET['embbed_groupby']) && 'Y' == $_GET['embbed_groupby'];
           $this->tbar_pos     = filter_input(INPUT_GET, 'toolbar_pos', FILTER_SANITIZE_SPECIAL_CHARS);
           $this->sc_origem    = filter_input(INPUT_GET, 'script_origem', FILTER_SANITIZE_STRING);
       }
       else
       {
           exit;
       }
       if (isset($_POST['ajax_ctrl']) && $_POST['ajax_ctrl'] == "proc_ajax")
       {
           $this->proc_ajax = true;
       }
       $this->ajax_return = array();
       $this->path_grid_sv = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv'];
       if (!isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid']))
       {
           $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'] = true;
       }
       if (isset($_POST['Fsave_ok']) && $_POST['Fsave_ok'] == "default")
       {
           $this->Sel_restore_conf_grid();
       }
       elseif (isset($_POST['Fsave_ok']) && $_POST['Fsave_ok'] == "save_conf_grid")
       {
           $this->Sel_save_conf_grid($_POST['parm']);
       }
       elseif (isset($_POST['Fsave_ok']) && $_POST['Fsave_ok'] == "select_conf_grid")
       {
           $this->Sel_select_conf_grid($_POST['parm']);
       }
       elseif (isset($_POST['Fsave_ok']) && $_POST['Fsave_ok'] == "delete_conf_grid")
       {
           $this->Sel_delete_conf_grid($_POST['parm']);
       }
       if ($this->embbed)
       {
           ob_start();
           $this->Save_processa_form();
           $Temp = ob_get_clean();
           echo NM_charset_to_utf8($Temp);
       }
       else
       {
           $this->Save_processa_form();
       }
       exit;
    }

    function Sel_return_apl()
    {
       $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_ant'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq'];
       $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['contr_array_resumo'] = "NAO";
       $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['contr_total_geral']  = "NAO";
       unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral']);
       $this->ajax_return['exit'] = "ok";
       $this->ajax_return['setDisplay'][] = array('field' => 'id_btn_Brestore', 'value' => (!$_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'])?'':'none');
       ob_end_clean();
       $oJson = new Services_JSON();
       echo $oJson->encode($this->ajax_return);
       exit;
    }

    function Sel_clear_conf_grid()
    {
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort']);
        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular']);
    }

    function Sel_restore_conf_grid()
    {
        $this->Sel_clear_conf_grid();
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort_SV'];
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular_SV']))
        {
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular_SV'];
        }
        $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'] = true;
        $this->Sel_return_apl();
    }

    function Sel_save_conf_grid($parms)
    {
        $NM_str_save = array();
        $cada_parm   = explode('*NM@', $parms);
        $save_option = $cada_parm[0];
        $save_name   = $cada_parm[1];
        $NM_str_save[] = "str@NMF@SC_Save_Name@NMF@" . $save_name . "@NMF@";
        $save_name = str_replace('/', ' ', $save_name);
        $save_name = str_replace('\\', ' ', $save_name);
        $save_name = str_replace('.', ' ', $save_name);
        if (!NM_is_utf8($save_name))
        {
            $save_name = sc_convert_encoding($save_name, "UTF-8", $_SESSION['scriptcase']['charset']);
        }
        $NM_patch = $this->path_grid_sv;
        if (!is_dir($NM_patch))
        {
            $NMdir = mkdir($NM_patch, 0755);
        }
        $NM_patch .= "pasaporte_beta2/";
        if (!is_dir($NM_patch))
        {
            $NMdir = mkdir($NM_patch, 0755);
        }
        $NM_patch .= "grid_clientes/";
        if (!is_dir($NM_patch))
        {
            $NMdir = mkdir($NM_patch, 0755);
        }
        $Parms_usr  = "";
        $NM_arq_grid = fopen ($NM_patch . $save_name, 'w');

        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby']))
        {
            $NM_str_save[] = "str@NMF@SC_Ind_Groupby@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp']))
        {
            $NM_str_save[] = "arr@NMF@SC_Gb_Free_cmp@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql']))
        {
            $NM_str_save[] = "arr@NMF@SC_Gb_Free_sql@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order']))
        {
            $NM_str_save[] = "arr@NMF@field_order@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig']))
        {
            $NM_str_save[] = "arr@NMF@field_order_orig@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display']))
        {
            $NM_str_save[] = "arr@NMF@field_display@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel']))
        {
            $NM_str_save[] = "arr@NMF@usr_cmp_sel@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select']))
        {
            $NM_str_save[] = "arr@NMF@ordem_select@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']))
        {
            $NM_str_save[] = "arr@NMF@ordem_quebra@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro']))
        {
            $NM_str_save[] = "str@NMF@where_pesq_filtro@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq']))
        {
            $NM_str_save[] = "str@NMF@where_pesq@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq']))
        {
            $NM_str_save[] = "str@NMF@cond_pesq@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca']))
        {
            $NM_str_save[] = "arr@NMF@campos_busca@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search']))
        {
            $NM_str_save[] = "arr@NMF@dyn_search@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op']))
        {
            $NM_str_save[] = "str@NMF@dyn_search_op@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out']))
        {
            $NM_str_save[] = "arr@NMF@dyn_search_out@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search']))
        {
            $NM_str_save[] = "arr@NMF@cond_dyn_search@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search']))
        {
            $NM_str_save[] = "arr@NMF@Grid_search@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq']))
        {
            $NM_str_save[] = "arr@NMF@grid_pesq@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid']))
        {
            $NM_str_save[] = "str@NMF@ordem_grid@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant']))
        {
            $NM_str_save[] = "str@NMF@ordem_ant@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc']))
        {
            $NM_str_save[] = "str@NMF@ordem_desc@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp']))
        {
            $NM_str_save[] = "str@NMF@ordem_cmp@NMF@" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp'] . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order']))
        {
            $NM_str_save[] = "arr@NMF@summarizing_fields_order@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display']))
        {
            $NM_str_save[] = "arr@NMF@summarizing_fields_display@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral']))
        {
            $NM_str_save[] = "arr@NMF@tot_geral@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by']))
        {
            $NM_str_save[] = "arr@NMF@pivot_group_by@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys']))
        {
            $NM_str_save[] = "arr@NMF@pivot_x_axys@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys']))
        {
            $NM_str_save[] = "arr@NMF@pivot_y_axys@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill']))
        {
            $NM_str_save[] = "arr@NMF@pivot_fill@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order']))
        {
            $NM_str_save[] = "arr@NMF@pivot_order@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col']))
        {
            $NM_str_save[] = "arr@NMF@pivot_order_col@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level']))
        {
            $NM_str_save[] = "arr@NMF@pivot_order_level@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort']))
        {
            $NM_str_save[] = "arr@NMF@pivot_order_sort@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort']) . "@NMF@";
        }
        if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular']))
        {
            $NM_str_save[] = "arr@NMF@pivot_tabular@NMF@" . serialize($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular']) . "@NMF@";
        }

        foreach ($NM_str_save as $ind => $cada_lin_save)
        {
            if (!NM_is_utf8($cada_lin_save))
            {
               $cada_lin_save = sc_convert_encoding($cada_lin_save, "UTF-8", $_SESSION['scriptcase']['charset']);
            }
            fwrite($NM_arq_grid, $cada_lin_save . "\r\n");
        }
        fclose($NM_arq_grid);
    }

    function Sel_select_conf_grid($NM_arq_save)
    {
        if ($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'])
        {
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_display'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['usr_cmp_sel'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_select'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_filtro'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['where_pesq'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_pesq'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['campos_busca'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_op'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['dyn_search_out'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['cond_dyn_search'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['Grid_search'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['grid_pesq'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_grid'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_ant'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_desc'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_cmp'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_order'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['summarizing_fields_display'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['tot_geral'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_group_by'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_x_axys'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_y_axys'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_fill'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_col'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_level'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_order_sort'];
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular']))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular_SV'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['pivot_tabular'];
            }
            $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'] = false;
        }

        $this->Sel_clear_conf_grid();
        if(!isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'][ $NM_arq_save ])) return;
        $NM_arq_save = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'][ $NM_arq_save ];
        if ($_SESSION['scriptcase']['charset'] != "UTF-8" && NM_is_utf8($NM_arq_save))
        {
            $NM_arq_save = NM_conv_charset($NM_arq_save, $_SESSION['scriptcase']['charset'], "UTF-8");
        }
        $NM_patch = $this->path_grid_sv . "/" . $NM_arq_save;
        if (!is_file($NM_patch))
        {
            $NM_arq_save = sc_convert_encoding($NM_arq_save, "UTF-8", $_SESSION['scriptcase']['charset']);
            $NM_patch = $this->path_grid_sv . "/" . $NM_arq_save;
        }
        if (is_file($NM_patch))
        {
            $NM_arq_save = file($NM_patch);
            foreach ($NM_arq_save as $ind => $cada_lin_save)
            {
                if ($_SESSION['scriptcase']['charset'] != "UTF-8")
                {
                    $cada_lin_save = NM_conv_charset($cada_lin_save, $_SESSION['scriptcase']['charset'], "UTF-8");
                }
                $dados = explode("@NMF@", $cada_lin_save);
                if ($dados[1] == "SC_Save_Name")
                {
                }
                elseif ($dados[0] == "arr")
                {
                    $_SESSION['sc_session'][$this->sc_init]['grid_clientes'][$dados[1]] = unserialize($dados[2]);
                }
                else
                {
                    $_SESSION['sc_session'][$this->sc_init]['grid_clientes'][$dados[1]] = $dados[2];
                }
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order']))
            {
                foreach ($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order'] as $ind => $dados)
                {
                    if (!in_array($dados, $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_SV']))
                    {
                        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order'][$ind]);
                    }
                }
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig']))
            {
                foreach ($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig'] as $ind => $dados)
                {
                    if (!in_array($dados, $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig_SV']))
                    {
                        unset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['field_order_orig'][$ind]);
                    }
                }
            }
            if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby']) && !isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_All_Groupby'][$_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby']]))
            {
                $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Ind_Groupby_SV'];
                if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp']))
                {
                    $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_cmp_SV'];
                }
                if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql']))
                {
                    $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql'] = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['SC_Gb_Free_sql_SV'];
                }
                if (isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']))
                {
                    $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra']   = $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['ordem_quebra_SV'];
                }
            }
        }
        $this->Sel_return_apl();
    }

    function Sel_delete_conf_grid($NM_grid_del)
    {
        if(!isset($_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'][ $NM_grid_del ])) return;
        $NM_patch = $this->path_grid_sv . "/" . $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'][ $NM_grid_del ];
        if (!is_file($NM_patch))
        {
            $NM_grid_del = sc_convert_encoding($NM_grid_del, "UTF-8");
            $NM_patch = $this->path_grid_sv . "/" . $NM_grid_del;
        }
        if (is_file($NM_patch))
        {
            unlink($NM_patch);
        }
    }

    function Save_processa_form()
    {
         if ($this->proc_ajax)
         {
             ob_start();
         }
         $STR_lang    = (isset($_SESSION['scriptcase']['str_lang']) && !empty($_SESSION['scriptcase']['str_lang'])) ? $_SESSION['scriptcase']['str_lang'] : "es";
         $NM_arq_lang = "../_lib/lang/" . $STR_lang . ".lang.php";
         $this->Nm_lang = array();
         if (is_file($NM_arq_lang))
         {
             include_once($NM_arq_lang);
         }
         $_SESSION['scriptcase']['charset']  = (isset($this->Nm_lang['Nm_charset']) && !empty($this->Nm_lang['Nm_charset'])) ? $this->Nm_lang['Nm_charset'] : "UTF-8";
         foreach ($this->Nm_lang as $ind => $dados)
         {
            if ($_SESSION['scriptcase']['charset'] != "UTF-8" && NM_is_utf8($ind))
            {
                $ind = sc_convert_encoding($ind, $_SESSION['scriptcase']['charset'], "UTF-8");
                $this->Nm_lang[$ind] = $dados;
            }
            if ($_SESSION['scriptcase']['charset'] != "UTF-8" && NM_is_utf8($dados))
            {
                $this->Nm_lang[$ind] = sc_convert_encoding($dados, $_SESSION['scriptcase']['charset'], "UTF-8");
            }
         }
         $str_schema_all = (isset($_SESSION['scriptcase']['str_schema_all']) && !empty($_SESSION['scriptcase']['str_schema_all'])) ? $_SESSION['scriptcase']['str_schema_all'] : "Sc9_SweetAmour/Sc9_SweetAmour";
         include("../_lib/css/" . $str_schema_all . "_grid.php");
         $str_toolbar_separator     = trim($str_toolbar_separator);
         $Str_btn_grid = trim($str_button) . "/" . trim($str_button) . $_SESSION['scriptcase']['reg_conf']['css_dir'] . ".php";
         include("../_lib/buttons/" . $Str_btn_grid);
         if (!function_exists("nmButtonOutput"))
         {
             include_once("../_lib/lib/php/nm_gp_config_btn.php");
         }
         $this->gera_array_grid_save();
   if (!$this->embbed)
   {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
      <HTML<?php echo $_SESSION['scriptcase']['reg_conf']['html_dir'] ?>>
      <HEAD>
       <TITLE><?php echo $this->Nm_lang['lang_othr_grid_title'] ?> clientes</TITLE>
       <META http-equiv="Content-Type" content="text/html; charset=<?php echo $_SESSION['scriptcase']['charset_html'] ?>" />
<?php
if ($_SESSION['scriptcase']['proc_mobile'])
{
?>
   <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<?php
}
?>
       <META http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT"/>
       <META http-equiv="Last-Modified" content="<?php echo gmdate("D, d M Y H:i:s"); ?> GMT"/>
       <META http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate"/>
       <META http-equiv="Cache-Control" content="post-check=0, pre-check=0"/>
       <META http-equiv="Pragma" content="no-cache"/>
       <link rel="shortcut icon" href="../_lib/img/usr__NM__bg__NM__logo-sm.png">
       <link rel="stylesheet" type="text/css" href="../_lib/css/<?php echo $_SESSION['scriptcase']['css_popup'] ?>" /> 
       <link rel="stylesheet" type="text/css" href="../_lib/css/<?php echo $_SESSION['scriptcase']['css_popup_dir'] ?>" /> 
       <?php
       if(isset($_SESSION['scriptcase']['str_google_fonts']) && !empty($_SESSION['scriptcase']['str_google_fonts']))
       {
       ?>
          <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['scriptcase']['str_google_fonts'] ?>" />
       <?php
       }
       ?>
       <link rel="stylesheet" type="text/css" href="../_lib/buttons/<?php echo $_SESSION['scriptcase']['css_btn_popup'] ?>" /> 
       <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['sc_session']['path_third'] ?>/font-awesome/css/all.min.css" /> 
      </HEAD>
      <BODY class="scGridPage" style="margin: 0px; overflow-x: hidden">
      <script language="javascript" type="text/javascript" src="<?php echo $_SESSION['sc_session']['path_third'] ?>/jquery/js/jquery.js"></script>
      <script language="javascript" type="text/javascript" src="<?php echo $_SESSION['sc_session']['path_third'] ?>/tigra_color_picker/picker.js"></script>
<?php
   }
?>
<script language="javascript"> 
 //-------------------------------------
 function nm_save_grid()
 {
     if (document.Fsave.nmgp_save_name.value == '')
     {
        return;
     }
     parm  = document.Fsave.nmgp_save_option.value + '*NM@';
     parm += document.Fsave.nmgp_save_name.value + '*NM@';
     ajax_control('save_conf_grid', parm);
 }
 function nm_select_grid(str_path_save, str_display)
 {
     $('#id_save_used').html(': ' + str_display);
     ajax_control('select_conf_grid', str_path_save);
 }
 function nm_new_grid()
 {
     document.getElementById('id_btn_edit').style.display = 'none';
     document.getElementById('id_btn_save').style.display = '';
     document.getElementById('Edit_grid').style.display = 'none';
     document.getElementById('Salvar_grid').style.display = '';
     ajusta_window();
     document.Fsave.nmgp_save_name.focus();
 }
 function nm_cancel_new_grid()
 {
     document.getElementById('id_btn_edit').style.display = '';
     document.getElementById('id_btn_save').style.display = 'none';
     document.getElementById('Edit_grid').style.display = '';
     document.getElementById('Salvar_grid').style.display = 'none';
     ajusta_window();
 }
 function nm_del_grid(str_path_save)
 {
         ajax_control('delete_conf_grid', str_path_save);
 }
function ajax_control(opc, parm)
{
    if(opc == 'default' && parm == '')
    {
        $('#id_save_used').html('');
    }
    $.ajax({
      type: "POST",
      url: "grid_clientes_save_grid.php",
      data: "ajax_ctrl=proc_ajax&script_case_init=" + document.Fsave.script_case_init.value + "&script_case_session=" + document.Fsave.script_case_session.value + "&path_img=" + document.Fsave.path_img.value + "&path_btn=" + document.Fsave.path_btn.value + "&Fsave_ok=" + opc  + "&parm=" + parm
    })
     .done(function(jsonReturn) {
        var i, oResp;
        Tst_integrid = jsonReturn.trim();
        if ("{" != Tst_integrid.substr(0, 1)) {
            alert (jsonReturn);
            return;
        }
        eval("oResp = " + jsonReturn);
        if (oResp["setHtml"]) {
          for (i = 0; i < oResp["setHtml"].length; i++) {
               $("#" + oResp["setHtml"][i]["field"]).html(oResp["setHtml"][i]["value"]);
          }
        }
        if (oResp["setDisplay"]) {
          for (i = 0; i < oResp["setDisplay"].length; i++) {
               $("#" + oResp["setDisplay"][i]["field"]).css("display", oResp["setDisplay"][i]["value"]);
          }
        }
        if (oResp["exit"]) {
<?php
   if (!$this->embbed)
   {
?>
            self.parent.tb_remove(); 
<?php
   }
   $sParent = $this->embbed ? '' : 'parent.';
   if ($this->sc_origem == "cons")
   {
   echo $sParent . "nm_gp_submit_ajax('inicio', 'save_grid')"; 
   }
   else
   {
       echo $sParent . "nm_gp_move('resumo', '0');";
   }
?>
        }
        if (opc == 'save_conf_grid')
        {
            document.getElementById('input_save_name').value = '';
            nm_cancel_new_grid();
        }
        ajusta_window();
    });
}
 </script>
      <FORM name="Fsave" method="POST">
        <INPUT type="hidden" name="script_case_init" value="<?php echo NM_encode_input($this->sc_init); ?>"> 
        <INPUT type="hidden" name="script_case_session" value="<?php echo NM_encode_input($this->session); ?>"> 
        <INPUT type="hidden" name="path_img" value="<?php echo NM_encode_input($this->path_img); ?>"> 
        <INPUT type="hidden" name="path_btn" value="<?php echo NM_encode_input($this->path_btn); ?>"> 
        <INPUT type="hidden" name="script_origem" value="<?php echo NM_encode_input($this->sc_origem); ?>"> 
        <INPUT type="hidden" name="Fsave_ok" value="OK"> 
     <br />
<?php
if ($this->embbed)
{
    echo "<div class='scAppDivMoldura'>";
    echo "<table id=\"main_table\" style=\"width: 100%\" cellspacing=0 cellpadding=0>";
}
else
{
    echo "<table id=\"main_table\" align=\"center\">";
    ?>
    <tr>
    <td>
    <div class="scGridBorder">
    <table width='100%' cellspacing=0 cellpadding=0>
<?php
}
?>
 <tr>
  <td class="<?php echo ($this->embbed)? 'scAppDivHeader scAppDivHeaderText':'scGridLabelVert'; ?>">
   <?php echo $this->Nm_lang['lang_btns_gridsave_hint']; ?><span id='id_save_used'></span>
  </td>
 </tr>
 <tr>
  <td class="<?php echo ($this->embbed)? 'scAppDivContent scAppDivContentText':'scGridTabelaTd'; ?>">
   <table class="<?php echo ($this->embbed)? '':'scGridTabela'; ?>" style="border-width: 0; border-collapse: collapse; width:100%;" cellspacing=0 cellpadding=0>
    <tr class="<?php echo ($this->embbed)? '':'scGridFieldOddVert'; ?>">
     <td style="vertical-align: top">
     <table cellspacing=0 cellpadding=0 width='100%'>
      <tr id="Salvar_grid" style="display:none" ><td align="center">
        <table style="border-width: 0px; border-collapse: collapse" width="100%">
         <tr>
          <td>
              <?php echo $this->Nm_lang['lang_othr_nivel']; ?>
          </td>
          <td style="padding: 0px" valign="top">
           <SELECT class="<?php echo ($this->embbed)? 'scAppDivToolbarInput':'css_toolbar_obj'; ?>" id="id_save_option" name="nmgp_save_option" size="1">
            <option value=""></option>
            <option value="publico"><?php echo "" . $this->Nm_lang['lang_srch_public'] . "" ?></option>
           </SELECT>
           <BR>
        </tr>
        <tr>
          <td>
              <?php echo $this->Nm_lang['lang_othr_nome']; ?>
          </td>
          <td>
           <input id="input_save_name" class="<?php echo ($this->embbed)? 'scAppDivToolbarInput':'css_toolbar_obj'; ?>" type="text" name="nmgp_save_name" value="">
          </td>
         </tr>
        </table>
       </TD>
      </tr>
         <tr id="Edit_grid"><td align="center">
     <span id="select_recup">
<?php
         if ($this->proc_ajax)
         {
             ob_end_clean();
             ob_start();
         }
?>
         <table cellspacing=2 cellpadding=4 width='100%'>
         <?php
         foreach ($this->NM_grid_save as $level => $arr_level)
         {
             ?>
             <tr>
                 <td colspan='3'>
                            <?php echo $level; ?>
                 </td>
             </tr>
             <?php
            foreach ($arr_level as $save => $save_path)
            {
             ?>
             <tr>
                 <td width='1'>&nbsp;</td>
                 <td>
                            <?php echo $save; ?>
                 </td>
                 <td width='50' nowrap>
                         &nbsp;
                 </td>
                 <td width='200' nowrap>
                         <a href="#" onclick="nm_select_grid('<?php echo NM_encode_input($save_path); ?>', '<?php echo $level; ?> => <?php echo $save; ?>')" class="scGridPageLink"><?php echo $this->Nm_lang['lang_btns_apply']; ?></a>
                         &nbsp;
                         <img src='<?php echo $this->path_img; ?>/<?php echo $str_toolbar_separator; ?>' border='0'  align='absmiddle'>
                         &nbsp;
                         <a href="#" onclick="nm_del_grid('<?php echo NM_encode_input($save_path); ?>')" class="scGridPageLink"><?php echo $this->Nm_lang['lang_btns_dele']; ?></a>
                 </td>
             </tr>
             <?php
            }
         }
         ?>
         </table>
<?php
         if ($this->proc_ajax)
         {
             $this->ajax_return['setHtml'][] = array('field' => 'select_recup', 'value' => ob_get_contents());
             $this->ajax_return['setDisplay'][] = array('field' => 'id_btn_Brestore', 'value' => (!$_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'])?'':'none');
         }
?>
  &nbsp;&nbsp;&nbsp
     </span>
             </td>
         </tr>
         <tr><td class="<?php echo ($this->embbed)? 'scAppDivToolbar':'scGridToolbar'; ?>">
               <div id="id_btn_edit">
          <?php echo nmButtonOutput($this->arr_buttons, "bnovo_appdiv", "nm_new_grid();", "nm_new_grid();", "Ativa_save", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
  &nbsp;&nbsp;&nbsp;&nbsp;
<span id='id_btn_Brestore' style="display:<?php echo (!$_SESSION['sc_session'][$this->sc_init]['grid_clientes']['prim_save_grid'])?'':'none' ?>">
         <?php echo nmButtonOutput($this->arr_buttons, "brestore_appdiv", "ajax_control('default', '')", "ajax_control('default', '')", "Brestore", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
  &nbsp;&nbsp;&nbsp
</span>
<?php
   if (!$this->embbed)
   {
?>
   <?php echo nmButtonOutput($this->arr_buttons, "bsair_appdiv", "self.parent.tb_remove()", "self.parent.tb_remove()", "Bsair", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
<?php
   }
   else
   {
?>
   <?php echo nmButtonOutput($this->arr_buttons, "bsair_appdiv", "scBtnSaveGridHide('" . $this->tbar_pos . "');buttonunselectedSG();", "scBtnSaveGridHide('" . $this->tbar_pos . "');buttonunselectedSG();", "Bsair", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
<?php
   }
?>
               </div>
               <div id="id_btn_save" style="display:none">
           <?php echo nmButtonOutput($this->arr_buttons, "bsalvar_appdiv", "nm_save_grid()", "nm_save_grid()", "Save_frm", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
           <?php echo nmButtonOutput($this->arr_buttons, "bcancelar_appdiv", "nm_cancel_new_grid()", "nm_cancel_new_grid()", "Cancel_frm", "", "", "", "absmiddle", "", "0px", $this->path_btn, "", "", "", "", "", "only_text", "text_right", "", "", "", "", "", "", "");
?>
  &nbsp;&nbsp;&nbsp
               </div>
             </td>
         </tr>
   </table>
   </td></tr></table>
  </td>
  </tr>
 </table>
 </div>
 </td>
 </tr>
 </table>
</FORM>

<script language="javascript"> 
var bFixed = false;

function ajusta_window()
{
<?php
   if (!$this->embbed)
   {
?>
  var mt = $(document.getElementById("main_table"));
  if (0 == mt.width() || 0 == mt.height())
  {
    setTimeout("ajusta_window()", 50);
    return;
  }
  else if(!bFixed)
  {
    bFixed = true;
    if (navigator.userAgent.indexOf("Chrome/") > 0)
    {
      self.parent.tb_resize(mt.height() + 40, mt.width() + 40);
      setTimeout("ajusta_window()", 50);
      return;
    }
  }
  self.parent.tb_resize(mt.height() + 40, mt.width() + 40);
<?php
   }
?>
}
$( document ).ready(function() {
  <?php
  if (empty($this->NM_grid_save))
  {
      ?>
      nm_new_grid();
      <?php
  }
  ?>
  buttonSelectedSG();
  ajusta_window();
});
</script>
<script>
function buttonSelectedSG() {
   $("#save_grid_top").addClass("selected");
   $("#save_grid_bottom").addClass("selected");
}
function buttonunselectedSG() {
   $("#save_grid_top").removeClass("selected");
   $("#save_grid_bottom").removeClass("selected");
}
    buttonSelectedSG();
    ajusta_window()
</script>
<?php
   if (!$this->embbed)
   {
?>
</BODY>
</HTML>
<?php
  }
   if ($this->proc_ajax)
   {
       ob_end_clean();
       $oJson = new Services_JSON();
       echo $oJson->encode($this->ajax_return);
       exit;
   }

}
   function gera_array_grid_save()
   {
       $this->NM_grid_save = array();
       $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'] = array();
       $NM_patch   = "pasaporte_beta2/grid_clientes";
       if (is_dir($this->path_grid_sv . $NM_patch))
       {
           $NM_dir = @opendir($this->path_grid_sv . $NM_patch);
           while (FALSE !== ($NM_arq = @readdir($NM_dir)))
           {
             if (@is_file($this->path_grid_sv . $NM_patch . "/" . $NM_arq))
             {
                 $NM_sv_grid = file($this->path_grid_sv . $NM_patch . "/" . $NM_arq);
                 foreach ($NM_sv_grid as $ind => $cada_lin_save)
                 {
                     $dados = explode("@NMF@", $cada_lin_save);
                     if ($dados[1] == "SC_Save_Name")
                     {
                         $Name_save = $dados[2];
                         break;
                     }
                 }
                 if ($_SESSION['scriptcase']['charset'] != "UTF-8" && !$this->proc_ajax)
                 {
                     $Name_save = sc_convert_encoding($Name_save, $_SESSION['scriptcase']['charset'], "UTF-8");
                 }
                 if (!empty($Name_save))
                 {
                     $str_level = "" . $this->Nm_lang['lang_srch_public'] . "";
                     $this->NM_grid_save[$str_level][$Name_save] = md5($NM_patch . "/" . $NM_arq);
                     $_SESSION['sc_session'][$this->sc_init]['grid_clientes']['path_grid_sv_list'][md5($NM_patch . "/" . $NM_arq)] = $NM_patch . "/" . $NM_arq;
                 }
             }
           }
       }
   }
}
