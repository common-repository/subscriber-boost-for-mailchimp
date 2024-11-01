Vue.component('widget', {
    data: function () {
        return {
            inputEmail: ''
        }
    },
    props: [
        'brandColor',
        'gdprEnabled',
        'collapsedWidgetTitle',
        'expandedWidgetTitle',
        'expandedWidgetSubtitleInput',
        'expandedWidgetCallToActionButton',
        'mcUuid',
        'mcRegion',
        'mcListId',
        'show',
        'collapsed',
        'subscribed',
        'subscribeButtonClicked'
    ],
    template: ''
        + '<div class="sb-widget">'
        +     '<div class="sb-widget-collapsed" @click="expandWidget()" v-if="collapsed === true" :style="{ backgroundColor: brandColor }">'
        +         '<div class="sb-widget-collapsed__logo">'
        +             '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff">'
        +                 '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>'
        +                 '<path d="M0 0h24v24H0z" fill="none"/>'
        +             '</svg>'
        +         '</div>'
        +         '<div class="sb-widget-collapsed__contents">'
        +             '<div class="sb-widget-collapsed__contents__title">{{ collapsedWidgetTitle }}</div>'
        +         '</div>'
        +         '<div class="sb-widget-collapsed__logo">'
        +             '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        +                 '<polyline points="18 15 12 9 6 15" />'
        +             '</svg>'
        +         '</div>'
        +     '</div>'
        +     '<div v-else>'
        +         '<div class="sb-widget-expanded">'
        +             '<div class="sb-widget-expanded-collapse">'
        +                 '<span class="sb-widget-expanded-collapse__button" @click="collapseWidget()">'
        +                     '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        +                         '<polyline points="6 9 12 15 18 9" />'
        +                     '</svg>'
        +                 '</span>'
        +             '</div>'
        +             '<div class="sb-widget-expanded-headline">'
        +                 '<h4 class="sb-widget-expanded-headline__text">{{ expandedWidgetTitle }}</h4>'
        +             '</div>'
        +             '<div class="sb-widget-expanded-lead">'
        +                 '<div class="sb-widget-expanded-lead__text">{{ expandedWidgetSubtitleInput }}</div>'
        +             '</div>'
        +             '<div class="sb-widget-expanded-input">'
        +                 '<div v-if="subscribed" class="sb-widget-expanded-input-with-icon">'
        +                     '<input type="text" v-model="inputEmail" id="sb-widget-expanded-input__email" disabled="disabled" />'
        +                     '<svg class="sb-widget-expanded-input-with-icon__icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" :stroke="brandColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        +                         '<polyline points="20 6 9 17 4 12"/>'
        +                     '</svg>'
        +                 '</div>'
        +                 '<input v-else type="text" v-model="inputEmail" placeholder="Email Address" id="sb-widget-expanded-input__email" />'
        +             '</div>'
        +             '<div class="sb-widget-expanded-encouragement" :style="{ color: brandColor }">'
        +                 '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="9" viewBox="0 0 8 9" :fill="brandColor">'
        +                     '<path d="M8,1.45963308 L6.8382211,2.61685154 C6.29764536,2.01083157 5.5018769,1.62785863 4.61457319,1.62785863 C2.9860338,1.62785863 1.66584178,2.91794873 1.66584178,4.50935551 C1.66584178,6.10076229 2.9860338,7.39085239 4.61457319,7.39085239 C5.47980036,7.39085239 6.2579905,7.02669947 6.79740035,6.44667699 L6.79740035,6.50470646 L7.98477914,7.55650945 C7.14362508,8.44426992 5.94072539,9 4.60499939,9 C2.06172845,9 0,6.98528137 0,4.5 C0,2.01471863 2.06172845,0 4.60499939,0 C5.94880318,0 7.15816926,0.562471945 8,1.45963308 Z"></path>'
        +                     '<ellipse cx="4.61538462" cy="4.5" rx="1.84615385" ry="1.8"></ellipse>'
        +                 '</svg>'
        +                 '<span class="sb-widget-expanded-encouragement__text">Powered by <strong>ConvertWise</strong></span>'
        +             '</div>'
        +         '</div>'
        +         '<div v-if="subscribed" class="sb-widget-expanded-calltoaction" :style="{ backgroundColor: brandColor }">'
        +             '<strong>Subscribed!</strong>'
        +         '</div>'
        +         '<div v-else @click="newsletterSignup()" :class="[{ \'sb-widget-expanded-calltoaction__clicked\': subscribeButtonClicked }, \'sb-widget-expanded-calltoaction\']" :style="{ backgroundColor: brandColor }">{{ expandedWidgetCallToActionButton }}</div>'
        +     '</div>'
        + '</div>',
    methods: {
        slideUp: function () {
            this.show === true && this.collapsed === true && anime({ targets: this.$el, translateY: '-' + this.getWidgetHeight(), easing: 'linear', duration: 300 });
        },
        slideDown: function () {
            anime({ targets: this.$el, translateY: this.getWidgetHeight(), easing: 'linear', duration: 300 });
        },
        expandWidget: function () {
            this.$emit('collapse-widget', false)
        },
        collapseWidget: function () {
            this.$emit('collapse-widget', true)
        },
        newsletterSignup: function () {
            if (this.inputEmail === '' || this.inputEmail.indexOf('@') === -1 || this.inputEmail.indexOf('.') === -1) {
                return;
            }
            if (this.gdprEnabled === 'yes') {
                this.$emit('newsletter-signup-modal', { region: this.mcRegion, uuid: this.mcUuid, listId: this.mcListId, email: this.inputEmail })
            } else {
                this.$emit('newsletter-signup-request', { region: this.mcRegion, uuid: this.mcUuid, listId: this.mcListId, email: this.inputEmail })
            }
        },
        getWidgetHeight: function () {
            return window.getComputedStyle(this.$el).getPropertyValue('height');
        }
    },
    watch: {
        show: function (newValue, oldValue) {
            newValue === true ? this.slideUp() : this.slideDown()
        }
    },
    computed: {
        anime: function () {
            return window.anime;
        },
        axios: function () {
            return window.axios;
        }
    },
    mounted: function () {
        setTimeout(function () {
            widget.show = true;
            axios.get('/wp-json/subscriber-boost/v1/increment-widget-load-count')
                .then(response => { /* */ })
                .catch(error => { /* */ });
        }, 1000);
        window.addEventListener('resize', this.slideUp);
    },
    beforeDestroy: function () {
        window.removeEventListener('resize', this.slideUp);
    }
})

var widget = new Vue({
    el: '#app',
    data: {
        modalOpen: false,
        show: false,
        collapsed: true,
        subscribed: false,
        subscribeButtonClicked: false
    },
    methods: {
        toggleWidget: function (bool) {
            this.collapsed = bool
        },
        showSignupModal: function (payload) {
            if (this.modalOpen === true) {
                return;
            }
            this.subscribeButtonClicked = true
            this.modalOpen = true
            document.cookie = "MCPopupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC"
            document.cookie = "MCPopupSubscribed=; expires=Thu, 01 Jan 1970 00:00:00 UTC"
            this.startCookieWatch(payload.email)
            window.mailChimpModal.start({ "baseUrl": "mc." + payload.region + ".list-manage.com", "uuid": payload.uuid, "lid": payload.listId })
        },
        sendSignupRequest: function (payload) {
            this.subscribeButtonClicked = true
            axios.post('/wp-json/subscriber-boost/v1/subscribe-member-to-mailchimp-list', payload)
                .then(response => { this.subscribed = true; this.subscribeButtonClicked = false; })
                .catch(error => { /* */ });
        },
        startCookieWatch: function (email) {
            window.mcSubscriptionEmail   = email
            window.mcSubscriptionWatcher = setInterval(() => {
                if (document.cookie.indexOf('MCPopupSubscribed') > -1) {
                    this.subscribed = true
                    this.stopCookieWatch()
                }
                if (document.cookie.indexOf('MCPopupClosed') > -1) {
                    this.stopCookieWatch()
                }
                var iframe = document.querySelector('iframe[data-dojo-attach-point=iframeModalContainer]')
                if (iframe !== null) {
                    var innerDoc = iframe.contentDocument || iframe.contentWindow.document
                    var emailField = innerDoc.getElementById('mc-EMAIL')
                    if (emailField.value === '') {
                        emailField.value = window.mcSubscriptionEmail
                    }
                }
            }, 500);
        },
        stopCookieWatch: function () {
            clearInterval(window.mcSubscriptionWatcher);
            this.modalOpen = false
            this.subscribeButtonClicked = false
        }
    }
})
