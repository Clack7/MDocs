<template>
    <div>
        <div id="blueimp-gallery" class="blueimp-gallery">
        <!-- <div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls"> -->
            <div class="slides"></div>
            <h3 class="title"></h3>
            <a class="prev">‹</a>
            <a class="next">›</a>
            <a class="close">×</a>
            <!-- <a class="play-pause"></a> -->
            <ol class="indicator"></ol>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['refContainer'],
        data() {
            return {
                container: null,
            };
        },
        mounted() {
            let that = this;
            this.container = this.$parent.$refs[this.refContainer];
            $(this.container).on('click', 'img', function() {
                let list = [], index = 0, img = this;
                $(that.container).find('img').each(function(idx) {
                    if (this == img) {
                        index = idx;
                    }
                    list.push({
                        title: this.alt,
                        href: this.src,
                        type: 'image/png',
                        thumbnail: this.src
                    });
                });
                var gallery = blueimp.Gallery(list, {
                    index: index,
                    transitionSpeed: 0 //No animation
                });
            });
        }
    }
</script>
