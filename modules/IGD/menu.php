<?php if (FKTL == true) { ?>
<li class="<?php if ($params['module']=="IGD") {
    echo "active";
} ?>">
    <a href="javascript:void(0);" class="menu-toggle">
        <i class="material-icons">accessible</i>
        <span>Instalasi Gawat Darurat</span>
    </a>
    <ul class="ml-menu">
        <li class="<?php if ($params['module']=="IGD" && $params['page']=="index") {
    echo "active";
} ?>">
            <a href="<?php echo URL; ?>/?module=IGD&page=index">
                Pasien IGD
            </a>
        </li>
        <li class="<?php if ($params['module']=="IGD" && $params['page']=="lap_igd") {
    echo "active";
} ?>">
            <a href="<?php echo URL; ?>/?module=IGD&page=lap_igd">
                Laporan Pasien IGD
            </a>
        </li>
    </ul>
</li>
<?php } ?>
