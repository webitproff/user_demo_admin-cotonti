<!-- BEGIN: MAIN -->
<div class="container-fluid py-4">
    <h2>{PHP.L.user_demo_admin_title}</h2>
    {FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {TAB_LIST_ACTIVE}" href="{URL_LIST}">{PHP.L.user_demo_admin_tab_list}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {TAB_CREATE_ACTIVE}" href="{URL_CREATE}">{PHP.L.user_demo_admin_tab_create}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {TAB_RIGHTS_ACTIVE}" href="{URL_RIGHTS}">{PHP.L.user_demo_admin_tab_rights}</a>
        </li>
    </ul>

    <!-- IF {PHP.tab} == 'list' -->
    <div class="mb-3">
        {PHP.L.user_demo_admin_total}: <strong>{LIST_TOTAL}</strong>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{PHP.L.user_demo_admin_col_name}</th>
                    <th>Email</th>
                    <th>{PHP.L.user_demo_admin_col_regdate}</th>
                </tr>
            </thead>
            <tbody>
                <!-- BEGIN: LIST_ROW -->
                <tr>
                    <td>{LIST_ROW_ID}</td>
                    <td><a href="{LIST_ROW_URL}" target="_blank">{LIST_ROW_NAME}</a></td>
                    <td>{LIST_ROW_EMAIL}</td>
                    <td>{LIST_ROW_REGDATE}</td>
                </tr>
                <!-- END: LIST_ROW -->
                <!-- BEGIN: LIST_EMPTY -->
                <tr>
                    <td colspan="4" class="text-center">{PHP.L.user_demo_admin_no_users}</td>
                </tr>
                <!-- END: LIST_EMPTY -->
            </tbody>
        </table>
    </div>

    <!-- IF {PAGINATION} -->
    <nav class="mt-3">
        <div class="text-center mb-2">
            {PHP.L.Total}: {TOTAL_ENTRIES}, {PHP.L.Onpage}: {ENTRIES_ON_CURRENT_PAGE}
        </div>
        <ul class="pagination justify-content-center">
            {PREVIOUS_PAGE}{PAGINATION}{NEXT_PAGE}
        </ul>
    </nav>
    <!-- ENDIF -->
    <!-- ENDIF -->

    <!-- IF {PHP.tab} == 'create' -->
    <div class="card p-4">
        <form method="post" action="{CREATE_FORM_ACTION}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{PHP.L.user_demo_admin_col_name}</label>
                    {CREATE_USERNAME}
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    {CREATE_EMAIL}
                </div>
                <div class="col-md-6">
                    <label class="form-label">{PHP.L.user_demo_admin_col_password}</label>
                    {CREATE_PASSWORD1}
                </div>
                <div class="col-md-6">
                    <label class="form-label">{PHP.L.user_demo_admin_col_password_repeat}</label>
                    {CREATE_PASSWORD2}
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-4">
                <i class="fa-solid fa-user-plus me-1"></i> {PHP.L.user_demo_admin_save}
            </button>
        </form>
    </div>
    <!-- ENDIF -->

    <!-- IF {PHP.tab} == 'rights' -->
    <div class="card p-4">
        <p class="text-muted">{PHP.L.user_demo_admin_rights_help}</p>
        <form method="post" action="{RIGHTS_FORM_ACTION}">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>{PHP.L.user_demo_admin_module}</th>
                        <th class="text-center" style="width:120px">{PHP.L.user_demo_admin_allow}</th>
                        <th class="text-center" style="width:120px">{PHP.L.user_demo_admin_deny}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BEGIN: RIGHTS_ROW -->
                    <tr>
                        <td>{RIGHTS_ROW_TITLE}</td>
                        <td class="text-center">
                            <input type="radio" name="permission[{RIGHTS_ROW_CODE}]" value="1" {RIGHTS_ROW_ALLOW_CHECKED}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="permission[{RIGHTS_ROW_CODE}]" value="0" {RIGHTS_ROW_DENY_CHECKED}>
                        </td>
                    </tr>
                    <!-- END: RIGHTS_ROW -->
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary mt-3">
                {PHP.L.user_demo_admin_save_rights}
            </button>
        </form>
    </div>
    <!-- ENDIF -->
</div>
<!-- END: MAIN -->
