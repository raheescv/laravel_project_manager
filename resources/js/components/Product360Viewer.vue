<template>
  <div class="product-360-viewer" v-if="images.length > 0">
    <div class="viewer-container">
      <div class="image-container">
        <img
          :src="currentImage"
          :alt="currentImageAlt"
          class="main-image"
          @mousedown="startDrag"
          @mousemove="drag"
          @mouseup="endDrag"
          @mouseleave="endDrag"
          @touchstart="startTouch"
          @touchmove="touchMove"
          @touchend="endTouch"
        />
        <div class="loading-overlay" v-if="isLoading">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>

      <div class="controls">
        <div class="angle-indicator">
          <span class="current-angle">{{ currentAngle }}°</span>
        </div>

        <div class="navigation-controls">
          <button
            class="btn btn-outline-primary btn-sm"
            @click="previousImage"
            :disabled="isLoading"
          >
            <i class="fa fa-chevron-left"></i>
          </button>

          <div class="progress-container">
            <div class="progress" style="height: 6px;">
              <div
                class="progress-bar"
                :style="{ width: progressPercentage + '%' }"
              ></div>
            </div>
          </div>

          <button
            class="btn btn-outline-primary btn-sm"
            @click="nextImage"
            :disabled="isLoading"
          >
            <i class="fa fa-chevron-right"></i>
          </button>
        </div>

        <div class="play-controls">
          <button
            class="btn btn-outline-secondary btn-sm"
            @click="toggleAutoRotate"
            :class="{ 'btn-primary': isAutoRotating }"
          >
            <i class="fa" :class="isAutoRotate ? 'fa-pause' : 'fa-play'"></i>
            {{ isAutoRotating ? 'Pause' : 'Auto' }}
          </button>

          <button
            class="btn btn-outline-secondary btn-sm"
            @click="resetView"
          >
            <i class="fa fa-refresh"></i>
            Reset
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Product360Viewer',
  props: {
    images: {
      type: Array,
      required: true,
      default: () => []
    },
    autoRotateSpeed: {
      type: Number,
      default: 100 // milliseconds between frames
    },
    enableDrag: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      currentIndex: 0,
      isLoading: false,
      isAutoRotating: false,
      autoRotateInterval: null,
      isDragging: false,
      dragStartX: 0,
      dragStartIndex: 0,
      touchStartX: 0,
      touchStartIndex: 0
    }
  },
  computed: {
    currentImage() {
      if (this.images.length === 0) return '';
      return this.images[this.currentIndex]?.path || this.images[this.currentIndex]?.url || '';
    },
    currentImageAlt() {
      if (this.images.length === 0) return '';
      return this.images[this.currentIndex]?.alt_text || `360° view at ${this.currentAngle}°`;
    },
    currentAngle() {
      if (this.images.length === 0) return 0;
      return this.images[this.currentIndex]?.degree || (this.currentIndex * (360 / this.images.length));
    },
    progressPercentage() {
      if (this.images.length === 0) return 0;
      return ((this.currentIndex + 1) / this.images.length) * 100;
    }
  },
  methods: {
    nextImage() {
      if (this.isLoading) return;
      this.isLoading = true;
      this.currentIndex = (this.currentIndex + 1) % this.images.length;
      this.loadImage();
    },
    previousImage() {
      if (this.isLoading) return;
      this.isLoading = true;
      this.currentIndex = this.currentIndex === 0 ? this.images.length - 1 : this.currentIndex - 1;
      this.loadImage();
    },
    loadImage() {
      // Simulate loading time for smooth transitions
      setTimeout(() => {
        this.isLoading = false;
      }, 100);
    },
    toggleAutoRotate() {
      if (this.isAutoRotating) {
        this.stopAutoRotate();
      } else {
        this.startAutoRotate();
      }
    },
    startAutoRotate() {
      this.isAutoRotating = true;
      this.autoRotateInterval = setInterval(() => {
        this.nextImage();
      }, this.autoRotateSpeed);
    },
    stopAutoRotate() {
      this.isAutoRotating = false;
      if (this.autoRotateInterval) {
        clearInterval(this.autoRotateInterval);
        this.autoRotateInterval = null;
      }
    },
    resetView() {
      this.stopAutoRotate();
      this.currentIndex = 0;
      this.isLoading = false;
    },
    startDrag(event) {
      if (!this.enableDrag) return;
      this.isDragging = true;
      this.dragStartX = event.clientX;
      this.dragStartIndex = this.currentIndex;
      event.preventDefault();
    },
    drag(event) {
      if (!this.isDragging || !this.enableDrag) return;

      const deltaX = event.clientX - this.dragStartX;
      const sensitivity = 50; // pixels per image change
      const imageChange = Math.floor(deltaX / sensitivity);

      if (imageChange !== 0) {
        const newIndex = (this.dragStartIndex + imageChange) % this.images.length;
        if (newIndex < 0) {
          this.currentIndex = this.images.length + newIndex;
        } else {
          this.currentIndex = newIndex;
        }
        this.dragStartIndex = this.currentIndex;
        this.dragStartX = event.clientX;
      }
    },
    endDrag() {
      this.isDragging = false;
    },
    startTouch(event) {
      if (!this.enableDrag) return;
      this.touchStartX = event.touches[0].clientX;
      this.touchStartIndex = this.currentIndex;
    },
    touchMove(event) {
      if (!this.enableDrag) return;

      const deltaX = event.touches[0].clientX - this.touchStartX;
      const sensitivity = 50;
      const imageChange = Math.floor(deltaX / sensitivity);

      if (imageChange !== 0) {
        const newIndex = (this.touchStartIndex + imageChange) % this.images.length;
        if (newIndex < 0) {
          this.currentIndex = this.images.length + newIndex;
        } else {
          this.currentIndex = newIndex;
        }
        this.touchStartIndex = this.currentIndex;
        this.touchStartX = event.touches[0].clientX;
      }
    },
    endTouch() {
      // Touch ended
    }
  },
  mounted() {
    // Sort images by angle if they have degree property
    if (this.images.length > 0 && this.images[0].degree !== undefined) {
      this.images.sort((a, b) => (a.degree || 0) - (b.degree || 0));
    }
  },
  beforeUnmount() {
    this.stopAutoRotate();
  }
}
</script>

<style scoped>
.product-360-viewer {
  max-width: 600px;
  margin: 0 auto;
}

.viewer-container {
  position: relative;
  background: #f8f9fa;
  border-radius: 8px;
  overflow: hidden;
}

.image-container {
  position: relative;
  width: 100%;
  height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
              linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
              linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
              linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
  background-size: 20px 20px;
  background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
}

.main-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  cursor: grab;
  transition: transform 0.1s ease;
  user-select: none;
}

.main-image:active {
  cursor: grabbing;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
}

.controls {
  padding: 15px;
  background: white;
  border-top: 1px solid #dee2e6;
}

.angle-indicator {
  text-align: center;
  margin-bottom: 15px;
}

.current-angle {
  font-size: 1.2em;
  font-weight: bold;
  color: #007bff;
}

.navigation-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.progress-container {
  flex: 1;
}

.play-controls {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.btn {
  border-radius: 20px;
}

.progress {
  border-radius: 10px;
}

.progress-bar {
  background: linear-gradient(90deg, #007bff, #0056b3);
  border-radius: 10px;
  transition: width 0.3s ease;
}

@media (max-width: 768px) {
  .image-container {
    height: 300px;
  }

  .controls {
    padding: 10px;
  }

  .navigation-controls {
    flex-direction: column;
    gap: 8px;
  }

  .play-controls {
    flex-wrap: wrap;
  }
}
</style>
