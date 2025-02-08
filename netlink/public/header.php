<nav class="header navbar navbar-light fixed-top bg-blue w-100 border-bottom-1 m-0 p-0 border-bottom flex-nowrap">
    <div class="container-fluid d-flex justify-content-between w-100 pt-4 pe-5 ps-5">
        <a class="navbar-brand d-flex align-items-center justify-content-center align-self-start gap-2"
            href="index.php">
            <img src="logo.png" width="30" height="30" class="d-inline-block align-top" alt="">
            <p class="h3 m-0 p-0" style="color: #f8f9fa;">NetLink</p>

        </a>

        <div class="form-group has-search d-flex align-items-center flex-column">
            <form id="search-bar-form" autocomplete="off">
                <div class="autocomplete d-flex align-items-center" style="width:300px;">
                    <span class="bi bi-search form-control-feedback"></span>
                    <input id="search-bar" type="search" class="form-control mr-sm-2 bg-light" placeholder="Search"
                        aria-label="Search">
                </div>
                <ul id="search-results"
                    class="d-flex flex-column align-items-center justify-content-center m-0 p-0 mt-2 p-2 border rounded gap-2 hidden">
                </ul>
            </form>
        </div>

    </div>
</nav>
<style>
.bg-blue {
    background-color: #161160; 
}
span.bi.bi-search.form-control-feedback {
  top: 22px;
}
.text-light {
    color: #f8f9fa; 
}

.text-white {
    color: #ffffff; 
}

</style>    