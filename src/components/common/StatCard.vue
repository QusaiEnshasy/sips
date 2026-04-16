<template>
  <div 
    class="stat-card" 
    :class="[variantClass, { 'featured': featured }]"
    :style="customStyle"
    @click="$emit('click')"
  >
    <!-- أيقونة -->
    <div v-if="icon" class="icon-box" :class="iconClass">
      <i :class="icon"></i>
    </div>

    <!-- المحتوى -->
    <div class="stat-content">
      <div class="stat-label" v-if="label" v-text="label"></div>
      <div class="stat-value" v-if="value" v-text="value"></div>
      <div class="stat-trend" v-if="trend" :class="trendClass">
        <i :class="trendIcon"></i>
        {{ trend }}
      </div>
    </div>

    <!-- أيقونة خلفية (للتزيين) -->
    <i v-if="backgroundIcon" class="background-icon" :class="backgroundIcon"></i>

    <!-- محتوى إضافي عبر slot -->
    <slot></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // المحتوى الأساسي
  label: { type: String, default: '' },
  value: { type: [String, Number], default: '' },
  icon: { type: String, default: '' },
  
  // الاتجاه (زيادة/نقصان)
  trend: { type: String, default: '' },
  trendUp: { type: Boolean, default: true },
  
  // الأنماط
  variant: { 
    type: String, 
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(value)
  },
  featured: { type: Boolean, default: false },
  
  // أيقونة خلفية
  backgroundIcon: { type: String, default: '' },
  
  // ستايل مخصص
  customStyle: { type: Object, default: () => ({}) }
})

defineEmits(['click'])

// كلاس المتغير
const variantClass = computed(() => `stat-card-${props.variant}`)

// كلاس الأيقونة
const iconClass = computed(() => `icon-box-${props.variant}`)

// كلاس الاتجاه
const trendClass = computed(() => ({
  'trend-up': props.trendUp,
  'trend-down': !props.trendUp
}))

// أيقونة الاتجاه
const trendIcon = computed(() => 
  props.trendUp ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
)
</script>

<style scoped>
.stat-card {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  height: 100%;
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.stat-card.featured {
  background: linear-gradient(135deg, var(--accent) 0%, var(--primary-purple) 100%);
  color: white;
  border: none;
}

.stat-card.featured * {
  color: white !important;
}

.icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

/* أنماط الأيقونة حسب المتغير */
.icon-box-default {
  background: var(--accent-soft);
  color: var(--accent);
}

.icon-box-primary {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.icon-box-success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.icon-box-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.icon-box-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.icon-box-info {
  background: rgba(6, 182, 212, 0.1);
  color: #06b6d4;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-dark);
  line-height: 1.2;
  margin-bottom: 5px;
}

.stat-trend {
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 5px;
}

.trend-up {
  color: #22c55e;
}

.trend-down {
  color: #ef4444;
}

.background-icon {
  position: absolute;
  right: 15px;
  bottom: 10px;
  font-size: 2.5rem;
  opacity: 0.1;
  color: currentColor;
}

/* Responsive */
@media (max-width: 768px) {
  .stat-card {
    padding: 20px;
  }
  
  .stat-value {
    font-size: 24px;
  }
  
  .icon-box {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
}

@media (max-width: 576px) {
  .stat-card {
    padding: 18px;
  }
  
  .stat-value {
    font-size: 22px;
  }
}
</style>