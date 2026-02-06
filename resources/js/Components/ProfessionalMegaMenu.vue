<template>
  <nav class="professional-mega-menu">
    <div class="menu-container">
      <!-- Use Cases Menu -->
      <div 
        class="menu-item"
        @mouseenter="showMegaMenu('useCases')"
        @mouseleave="hideMegaMenu"
      >
        <button class="menu-trigger" :class="{ 'active': activeMenu === 'useCases' }">
          <span>Use Cases</span>
          <ChevronDown class="chevron-icon" />
        </button>
        
        <div v-if="activeMenu === 'useCases'" class="mega-menu-panel">
          <div class="mega-menu-content">
            <div class="menu-columns three-columns">
              <div class="menu-column">
                <h3 class="column-title">For Institutions</h3>
                <a href="/alumni-engagement" class="menu-link">
                  <Users class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Alumni Engagement</div>
                    <div class="link-description">Connect and engage with your alumni community through personalized experiences</div>
                  </div>
                </a>
                <a href="/fundraising" class="menu-link">
                  <DollarSign class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Fundraising & Donations</div>
                    <div class="link-description">Streamline donation campaigns and donor management with advanced analytics</div>
                  </div>
                </a>
                <a href="/events" class="menu-link">
                  <Calendar class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Events Management</div>
                    <div class="link-description">Organize and manage alumni events with seamless registration and tracking</div>
                  </div>
                </a>
                <a href="/communications" class="menu-link">
                  <Mail class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Alumni Communications</div>
                    <div class="link-description">Send targeted newsletters and updates to keep alumni informed</div>
                  </div>
                </a>
              </div>
              
              <div class="menu-column">
                <h3 class="column-title">For Employers</h3>
                <a href="/talent-acquisition" class="menu-link">
                  <Search class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Talent Acquisition</div>
                    <div class="link-description">Find and recruit top alumni talent from leading universities</div>
                  </div>
                </a>
                <a href="/networking" class="menu-link">
                  <Network class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Professional Networking</div>
                    <div class="link-description">Build valuable professional connections within alumni networks</div>
                  </div>
                </a>
                <a href="/partnerships" class="menu-link">
                  <Handshake class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">University Partnerships</div>
                    <div class="link-description">Collaborate with educational institutions for mutual growth</div>
                  </div>
                </a>
                <a href="/recruitment" class="menu-link">
                  <Briefcase class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Campus Recruitment</div>
                    <div class="link-description">Access fresh graduates and build your talent pipeline</div>
                  </div>
                </a>
              </div>
              
              <div class="menu-column">
                <h3 class="column-title">For Alumni</h3>
                <a href="/career-development" class="menu-link">
                  <TrendingUp class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Career Development</div>
                    <div class="link-description">Access resources and tools to advance your professional career</div>
                  </div>
                </a>
                <a href="/alumni-networking" class="menu-link">
                  <Users class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Alumni Networking</div>
                    <div class="link-description">Connect with fellow alumni and expand your professional network</div>
                  </div>
                </a>
                <a href="/mentorship" class="menu-link">
                  <UserCheck class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Mentorship Programs</div>
                    <div class="link-description">Participate in mentoring opportunities as mentor or mentee</div>
                  </div>
                </a>
                <a href="/professional-growth" class="menu-link">
                  <Award class="link-icon" />
                  <div class="link-content">
                    <div class="link-title">Professional Growth</div>
                    <div class="link-description">Continuous learning and skill development opportunities</div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Products Menu -->
      <div 
        class="menu-item"
        @mouseenter="showMegaMenu('products')"
        @mouseleave="hideMegaMenu"
      >
        <button class="menu-trigger" :class="{ 'active': activeMenu === 'products' }">
          <span>Products</span>
          <ChevronDown class="chevron-icon" />
        </button>
        
        <div 
          v-show="activeMenu === 'products'"
          class="mega-menu-panel products-panel"
        >
          <div class="mega-menu-content">
            <div class="menu-columns three-columns">
              <!-- Core Platform Column -->
              <div class="menu-column">
                <h3 class="column-title">Core Platform</h3>
                <ul class="menu-links">
                  <li>
                    <a href="/alumni-platform" class="menu-link">
                      <Users class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Alumni Platform</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/analytics-dashboard" class="menu-link">
                      <BarChart3 class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Analytics Dashboard</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/database-management" class="menu-link">
                      <Database class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Database Management</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/communication-tools" class="menu-link">
                      <MessageSquare class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Communication Tools</span>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
              
              <!-- Advanced Features Column -->
              <div class="menu-column">
                <h3 class="column-title">Advanced Features</h3>
                <ul class="menu-links">
                  <li>
                    <a href="/security" class="menu-link">
                      <Settings class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Enterprise Security</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/integrations" class="menu-link">
                      <Settings class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">System Integrations</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/automation" class="menu-link">
                      <Settings class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Workflow Automation</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/custom-reporting" class="menu-link">
                      <FileText class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Custom Reporting</span>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
              
              <!-- Global Solutions Column -->
              <div class="menu-column">
                <h3 class="column-title">Global Solutions</h3>
                <ul class="menu-links">
                  <li>
                    <a href="/global-network" class="menu-link">
                      <Network class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Global Network</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/mobile-app" class="menu-link">
                      <BookOpen class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Mobile Application</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/api-access" class="menu-link">
                      <Wrench class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">API Access</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/support" class="menu-link">
                      <HelpCircle class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">24/7 Support</span>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resources Menu -->
      <div 
        class="menu-item"
        @mouseenter="showMegaMenu('resources')"
        @mouseleave="hideMegaMenu"
      >
        <button class="menu-trigger" :class="{ 'active': activeMenu === 'resources' }">
          <span>Resources</span>
          <ChevronDown class="chevron-icon" />
        </button>
        
        <div 
          v-show="activeMenu === 'resources'"
          class="mega-menu-panel resources-panel"
        >
          <div class="mega-menu-content">
            <div class="menu-columns">
              <!-- Learn & Grow Column -->
              <div class="menu-column">
                <h3 class="column-title">Learn & Grow</h3>
                <ul class="menu-links">
                  <li>
                    <a href="/blog" class="menu-link">
                      <BookOpen class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Blog & Insights</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/webinars" class="menu-link">
                      <Video class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Webinars & Training</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/case-studies" class="menu-link">
                      <FileText class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Case Studies</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/documentation" class="menu-link">
                      <HelpCircle class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Documentation</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/community" class="menu-link">
                      <Users class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Community Forum</span>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
              
              <!-- Company Info Column -->
              <div class="menu-column">
                <h3 class="column-title">Company Info</h3>
                <ul class="menu-links">
                  <li>
                    <a href="/about" class="menu-link">
                      <Info class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">About Alumate</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/careers" class="menu-link">
                      <Briefcase class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Careers</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/contact" class="menu-link">
                      <Mail class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Contact Us</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a href="/support" class="menu-link">
                      <Phone class="link-icon" />
                      <div class="link-content">
                        <span class="link-title">Customer Support</span>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import {
  ChevronDown,
  GraduationCap,
  Users,
  BarChart,
  Briefcase,
  UserPlus,
  Scale,
  Star,
  TrendingUp,
  BookOpen,
  Handshake,
  Search,
  Video,
  Brain,
  Calendar,
  Trophy,
  FileText,
  PlayCircle,
  Wrench,
  CalendarDays,
  Info,
  DollarSign,
  Network,
  Mail,
  Database,
  MessageSquare,
  Settings,
  BarChart3,
  HelpCircle,
  Phone,
  Building,
  Shield,
  Zap,
  Globe,
  UserCheck,
  Award
} from 'lucide-vue-next'

const activeMenu = ref(null)

const showMegaMenu = (menuName) => {
  activeMenu.value = menuName
}

const hideMegaMenu = () => {
  activeMenu.value = null
}
</script>

<style scoped>
.professional-mega-menu {
  position: relative;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.menu-container {
  display: flex;
  align-items: center;
  gap: 3rem;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.menu-item {
  position: relative;
}

.menu-trigger {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem 0.5rem;
  background: none;
  border: none;
  font-size: 0.95rem;
  font-weight: 500;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.menu-trigger:hover,
.menu-trigger.active {
  color: #3b82f6;
}

.menu-trigger span {
  position: relative;
}

.menu-trigger:hover span::after,
.menu-trigger.active span::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  right: 0;
  height: 2px;
  background-color: #3b82f6;
  border-radius: 1px;
}

@keyframes slideIn {
  from {
    transform: scaleX(0);
  }
  to {
    transform: scaleX(1);
  }
}

.chevron-icon {
  width: 1rem;
  height: 1rem;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-item:hover .chevron-icon,
.menu-trigger.active .chevron-icon {
  transform: rotate(180deg);
  color: #3b82f6;
}

.mega-menu-panel {
  position: absolute;
  top: 100%;
  left: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0 0 0.5rem 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  z-index: 50;
  min-width: 700px;
  animation: fadeIn 0.2s ease-out;
  overflow: hidden;
}

.products-panel {
  min-width: 900px;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.mega-menu-content {
  padding: 2rem;
  background: white;
}

.menu-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
}

.three-columns {
  grid-template-columns: 1fr 1fr 1fr;
  gap: 3rem;
}

.menu-column {
  display: flex;
  flex-direction: column;
  position: relative;
}

.menu-column::before {
  content: '';
  position: absolute;
  top: 0;
  left: -2rem;
  width: 1px;
  height: 100%;
  background: #e5e7eb;
}

.menu-column:first-child::before {
  display: none;
}

.column-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1.25rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.menu-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.menu-link {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  text-decoration: none;
  color: #4b5563;
  transition: all 0.2s ease;
  margin-bottom: 0.25rem;
  border: 1px solid transparent;
}

.menu-link:hover {
  background: #f9fafb;
  color: #1f2937;
  border-color: #e5e7eb;
}

.link-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.menu-link:hover .link-icon {
  color: #3b82f6;
}

.link-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.link-title {
  font-weight: 500;
  font-size: 0.9rem;
  line-height: 1.4;
  color: #1f2937;
}

.link-description {
  font-size: 0.75rem;
  color: #6b7280;
  line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .menu-container {
    gap: 2rem;
    padding: 0 1.5rem;
  }
  
  .mega-menu-panel {
    min-width: 600px;
  }
  
  .products-panel {
    min-width: 800px;
  }
}

@media (max-width: 1024px) {
  .mega-menu-panel {
    min-width: 500px;
  }
  
  .products-panel {
    min-width: 650px;
  }
  
  .menu-columns {
    gap: 2.5rem;
  }
  
  .three-columns {
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }
  
  .mega-menu-content {
    padding: 2rem;
  }
}

@media (max-width: 768px) {
  .professional-mega-menu {
    display: none;
  }
}

@media (max-width: 640px) {
  .mega-menu-panel {
    min-width: 320px;
    left: 1rem;
    right: 1rem;
    transform: none;
    border-radius: 0.75rem;
  }
  
  .mega-menu-content {
    padding: 1.5rem;
  }
  
  .menu-columns {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .menu-column::before {
    display: none;
  }
  
  .column-title {
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }
  
  .menu-link {
    padding: 0.875rem 1rem;
    margin-bottom: 0.5rem;
  }
}
</style>