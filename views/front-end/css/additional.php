<style type="text/css" id="appoitments-additional">
<?php echo $additional_css; ?>

<?php foreach( $colors as $selctor => $value ) { ?>
td.<?php echo esc_attr( $selctor ); ?>, div.<?php echo esc_attr( $selctor ); ?> {
    background-color: <?php echo esc_attr( $value ); ?> !important;
}
<?php } ?>
</style>

