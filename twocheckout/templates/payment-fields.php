<?php
if ($this->description) {
    echo '<p>' . $this->description . '</p>';
}
?>
<fieldset>
    <div id="tcoApiForm">
        <div id="tcoWait">
            <div class="text">
                <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/spinner.gif'; ?>">
                Processing, please wait...
            </div>
        </div>
        <input type="hidden" id="ess_token" name="ess_token" value="">
        <input type="hidden" id="is_guest" name="guest" value="">
        <div id="tco_error"></div>
        <div id="tco-payment-form" data-json="<?php echo str_replace("\"", "'", $this->custom_style); ?>">
            <div id="card-element">
                <div id="load">Loading, please wait...</div>
                <!-- A TCO IFRAME will be inserted here. -->
            </div>
        </div>
    </div>

    <script type="text/javascript">
        let seller_id = "<?php echo $this->seller_id ?>";
        let defaultStyle = "<?php echo $this->default_style;?>";
    </script>
</fieldset>
