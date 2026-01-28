<template>
    <Transition name="fade">
        <div v-if="show" class="loading-overlay">
            <div class="loading-content">
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <div class="spinner-ring"></div>
                </div>
                <p class="loading-text">{{ text || 'Loading...' }}</p>
            </div>
        </div>
    </Transition>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false
    },
    text: {
        type: String,
        default: 'Loading...'
    }
})
</script>

<style scoped>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-content {
    text-align: center;
    color: white;
}

.spinner-container {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
}

.spinner {
    width: 80px;
    height: 80px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top: 4px solid #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    position: absolute;
    top: 0;
    left: 0;
}

.spinner-ring {
    width: 60px;
    height: 60px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-right: 3px solid #fff;
    border-radius: 50%;
    animation: spin-reverse 0.8s linear infinite;
    position: absolute;
    top: 10px;
    left: 10px;
}

.loading-text {
    font-size: 16px;
    font-weight: 500;
    margin-top: 10px;
    color: #fff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes spin-reverse {
    0% {
        transform: rotate(360deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

