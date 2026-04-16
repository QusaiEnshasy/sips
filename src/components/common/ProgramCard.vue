<template>
  <div 
    class="program-card" 
    :class="{ 'featured': featured }"
    @click="$emit('click')"
  >
    <!-- Header with rating and favorite -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="rating-badge">
        <i class="bi bi-star-fill me-1"></i>
        {{ rating }}
        <small class="text-muted fw-normal">({{ enrolled }} {{ t('enrolled') }})</small>
      </div>
      <button 
        class="favorite-btn" 
        @click.stop="$emit('toggle-favorite')"
        :class="{ 'active': isFavorite }"
      >
        <i :class="isFavorite ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
      </button>
    </div>

    <!-- Title -->
    <h5 class="fw-bold mb-3 lh-base">{{ title }}</h5>

    <!-- Tags -->
    <div class="tags-container mb-3 d-flex gap-2 flex-wrap">
      <span class="tag" :class="`tag-${level}`">{{ t(level) }}</span>
      <span class="tag" :class="`tag-${mode}`">{{ t(mode) }}</span>
    </div>

    <!-- Description -->
    <p class="text-muted small mb-4">{{ description }}</p>

    <!-- Skills -->
    <div class="skills-container mb-4 d-flex flex-wrap gap-2">
      <span 
        v-for="skill in displayedSkills" 
        :key="skill"
        class="skill-item"
      >
        {{ skill }}
      </span>
      <span v-if="skills.length > 3" class="skill-item text-primary">
        +{{ skills.length - 3 }} {{ t('more') }}
      </span>
    </div>

    <!-- Duration and Start Date -->
    <div class="details-box mb-4">
      <div>
        <small class="text-muted d-block mb-1">
          <i class="bi bi-clock me-1"></i> {{ t('duration') }}
        </small>
        <span class="fw-bold small">{{ duration }}</span>
      </div>
      <div>
        <small class="text-muted d-block mb-1">
          <i class="bi bi-calendar-event me-1"></i> {{ t('starts') }}
        </small>
        <span class="fw-bold small">{{ startDate }}</span>
      </div>
    </div>

    <!-- Instructor and Apply Button -->
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <img 
          :src="instructorAvatar" 
          class="instructor-img rounded-circle" 
          width="35"
          :alt="instructorName"
        />
        <div style="line-height: 1">
          <small class="fw-bold d-block mb-1">{{ instructorName }}</small>
          <small class="text-muted" style="font-size: 10px">{{ instructorTitle }}</small>
        </div>
      </div>
      <button 
        class="btn-apply" 
        @click.stop="$emit('apply')"
      >
        {{ t('apply_now') }} <i class="bi bi-arrow-right ms-2"></i>
      </button>
    </div>

    <!-- Additional content slot -->
    <slot></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const props = defineProps({
  // Basic info
  title: { type: String, required: true },
  description: { type: String, required: true },
  
  // Rating and enrollment
  rating: { type: Number, default: 0 },
  enrolled: { type: Number, default: 0 },
  
  // Tags
  level: { type: String, default: 'intermediate' },
  mode: { type: String, default: 'online' },
  
  // Skills
  skills: { type: Array, default: () => [] },
  
  // Duration and dates
  duration: { type: String, required: true },
  startDate: { type: String, required: true },
  
  // Instructor
  instructorName: { type: String, required: true },
  instructorTitle: { type: String, required: true },
  instructorAvatar: { type: String, default: '' },
  
  // State
  isFavorite: { type: Boolean, default: false },
  featured: { type: Boolean, default: false }
})

defineEmits(['click', 'toggle-favorite', 'apply'])

// Display only first 3 skills
const displayedSkills = computed(() => {
  return props.skills.slice(0, 3)
})
</script>

<style scoped>
.program-card {
  background: var(--card-bg);
  border-radius: 28px;
  padding: 30px;
  border: 1px solid transparent;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  height: 100%;
  position: relative;
  cursor: pointer;
}

.program-card:hover {
  transform: translateY(-8px);
  border-color: var(--accent);
  box-shadow: 0 25px 50px rgba(124, 58, 237, 0.1);
}

.program-card.featured {
  border: 2px solid var(--accent);
  background: linear-gradient(135deg, var(--card-bg), var(--accent-soft));
}

.rating-badge {
  background: #fff9db;
  color: #f08c00;
  padding: 5px 12px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
}

.favorite-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.favorite-btn:hover {
  background: #fee2e2;
  border-color: #fecaca;
}

.favorite-btn.active {
  background: #fee2e2;
  color: #ef4444;
  border-color: #fecaca;
}

.tag {
  padding: 6px 14px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.tag-advanced {
  background: #fee2e2;
  color: #ef4444;
}

.tag-intermediate {
  background: #fef3c7;
  color: #d97706;
}

.tag-beginner {
  background: #e0f2fe;
  color: #0284c7;
}

.tag-online {
  background: #e0f2fe;
  color: #0284c7;
}

.tag-hybrid {
  background: #f3f0ff;
  color: var(--accent);
}

.tag-onsite {
  background: #e0e7ff;
  color: #4f46e5;
}

.skill-item {
  background: #f1f5f9;
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 11px;
  color: #475569;
  font-weight: 500;
}

.details-box {
  background: var(--main-bg);
  border-radius: 20px;
  padding: 15px;
  display: flex;
  justify-content: space-between;
  border: 1px solid var(--border-color);
}

.instructor-img {
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid var(--accent-soft);
}

.instructor-img:hover {
  transform: scale(1.2) rotate(5deg);
  border-color: var(--accent);
}

.btn-apply {
  background: linear-gradient(135deg, var(--accent), var(--primary-purple));
  border: none;
  color: white;
  padding: 10px 20px;
  border-radius: 15px;
  font-weight: 600;
  font-size: 13px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.btn-apply:hover {
  box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
  transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
  .program-card {
    padding: 25px;
  }
  
  .details-box {
    flex-direction: column;
    gap: 10px;
  }
  
  .btn-apply {
    padding: 8px 16px;
    font-size: 12px;
  }
}

@media (max-width: 576px) {
  .program-card {
    padding: 20px;
  }
  
  .d-flex.justify-content-between {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 10px;
  }
  
  .favorite-btn {
    align-self: flex-end;
  }
  
  .btn-apply {
    width: 100%;
    justify-content: center;
  }
}
</style>