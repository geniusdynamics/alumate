import { ref, computed, onMounted } from 'vue'

export interface SpamProtectionConfig {
  enableHoneypot: boolean
  enableTimingAnalysis: boolean
  enableBehaviorAnalysis: boolean
  enableContentAnalysis: boolean
  enableRateLimiting: boolean
  minFormFillTime: number // milliseconds
  maxFormFillTime: number // milliseconds
  maxSubmissionRate: number // submissions per minute
  suspiciousKeywords: string[]
  blockedUserAgents: string[]
}

export interface SpamAnalysisResult {
  isSpam: boolean
  confidence: number
  reasons: string[]
  warnings: string[]
  blockSubmission: boolean
}

export function useSpamProtection(config: Partial<SpamProtectionConfig> = {}) {
  const defaultConfig: SpamProtectionConfig = {
    enableHoneypot: true,
    enableTimingAnalysis: true,
    enableBehaviorAnalysis: true,
    enableContentAnalysis: true,
    enableRateLimiting: true,
    minFormFillTime: 3000, // 3 seconds minimum
    maxFormFillTime: 1800000, // 30 minutes maximum
    maxSubmissionRate: 5, // 5 submissions per minute
    suspiciousKeywords: [
      'viagra', 'cialis', 'casino', 'lottery', 'winner', 'congratulations',
      'urgent', 'act now', 'limited time', 'free money', 'guaranteed',
      'no obligation', 'risk free', 'call now', 'click here', 'buy now'
    ],
    blockedUserAgents: [
      'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'
    ]
  }

  const spamConfig = { ...defaultConfig, ...config }
  const formStartTime = ref<number>(0)
  const keystrokes = ref<number>(0)
  const mouseMovements = ref<number>(0)
  const focusEvents = ref<number>(0)
  const pasteEvents = ref<number>(0)
  const submissionAttempts = ref<number>(0)
  const lastSubmissionTime = ref<number>(0)
  const honeypotValue = ref<string>('')
  const behaviorScore = ref<number>(0)
  
  const isRateLimited = computed(() => {
    if (!spamConfig.enableRateLimiting) return false
    
    const now = Date.now()
    const timeSinceLastSubmission = now - lastSubmissionTime.value
    const minutesSinceLastSubmission = timeSinceLastSubmission / (1000 * 60)
    
    return submissionAttempts.value >= spamConfig.maxSubmissionRate && 
           minutesSinceLastSubmission < 1
  })

  const initializeSpamProtection = () => {
    formStartTime.value = Date.now()
    resetBehaviorTracking()
    
    if (spamConfig.enableBehaviorAnalysis) {
      setupBehaviorTracking()
    }
  }

  const resetBehaviorTracking = () => {
    keystrokes.value = 0
    mouseMovements.value = 0
    focusEvents.value = 0
    pasteEvents.value = 0
    behaviorScore.value = 0
  }

  const setupBehaviorTracking = () => {
    // Track keystrokes
    const handleKeydown = () => {
      keystrokes.value++
    }

    // Track mouse movements
    const handleMouseMove = () => {
      mouseMovements.value++
    }

    // Track focus events
    const handleFocus = () => {
      focusEvents.value++
    }

    // Track paste events
    const handlePaste = () => {
      pasteEvents.value++
    }

    document.addEventListener('keydown', handleKeydown)
    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('focus', handleFocus, true)
    document.addEventListener('paste', handlePaste, true)

    // Cleanup function
    return () => {
      document.removeEventListener('keydown', handleKeydown)
      document.removeEventListener('mousemove', handleMouseMove)
      document.removeEventListener('focus', handleFocus, true)
      document.removeEventListener('paste', handlePaste, true)
    }
  }

  const analyzeFormTiming = (): { isValid: boolean; reasons: string[] } => {
    if (!spamConfig.enableTimingAnalysis) {
      return { isValid: true, reasons: [] }
    }

    const fillTime = Date.now() - formStartTime.value
    const reasons: string[] = []

    if (fillTime < spamConfig.minFormFillTime) {
      reasons.push('Form filled too quickly (possible bot)')
    }

    if (fillTime > spamConfig.maxFormFillTime) {
      reasons.push('Form session too long (possible abandoned session)')
    }

    return {
      isValid: reasons.length === 0,
      reasons
    }
  }

  const analyzeBehavior = (): { score: number; reasons: string[] } => {
    if (!spamConfig.enableBehaviorAnalysis) {
      return { score: 0, reasons: [] }
    }

    let score = 0
    const reasons: string[] = []

    // Analyze keystroke patterns
    if (keystrokes.value === 0) {
      score += 0.3
      reasons.push('No keyboard interaction detected')
    } else if (keystrokes.value < 10) {
      score += 0.1
      reasons.push('Very few keystrokes detected')
    }

    // Analyze mouse movements
    if (mouseMovements.value === 0) {
      score += 0.2
      reasons.push('No mouse movement detected')
    } else if (mouseMovements.value < 5) {
      score += 0.1
      reasons.push('Very little mouse movement')
    }

    // Analyze focus events
    if (focusEvents.value === 0) {
      score += 0.2
      reasons.push('No focus events detected')
    }

    // Analyze paste events (high paste ratio might indicate copy-paste spam)
    const pasteRatio = pasteEvents.value / Math.max(keystrokes.value, 1)
    if (pasteRatio > 0.8) {
      score += 0.3
      reasons.push('High paste-to-keystroke ratio')
    }

    // Check for inhuman speed
    const fillTime = Date.now() - formStartTime.value
    const keystrokesPerSecond = keystrokes.value / (fillTime / 1000)
    if (keystrokesPerSecond > 10) {
      score += 0.4
      reasons.push('Typing speed too fast for human')
    }

    return { score: Math.min(score, 1), reasons }
  }

  const analyzeContent = (formData: Record<string, any>): { score: number; reasons: string[] } => {
    if (!spamConfig.enableContentAnalysis) {
      return { score: 0, reasons: [] }
    }

    let score = 0
    const reasons: string[] = []

    // Analyze text content for spam keywords
    const textContent = Object.values(formData)
      .filter(value => typeof value === 'string')
      .join(' ')
      .toLowerCase()

    const suspiciousKeywordCount = spamConfig.suspiciousKeywords.filter(keyword =>
      textContent.includes(keyword.toLowerCase())
    ).length

    if (suspiciousKeywordCount > 0) {
      score += Math.min(suspiciousKeywordCount * 0.2, 0.6)
      reasons.push(`Contains ${suspiciousKeywordCount} suspicious keyword(s)`)
    }

    // Check for excessive links
    const linkCount = (textContent.match(/https?:\/\/\S+/g) || []).length
    if (linkCount > 3) {
      score += 0.3
      reasons.push('Contains excessive links')
    }

    // Check for excessive punctuation
    const punctuationCount = (textContent.match(/[!?]{2,}/g) || []).length
    if (punctuationCount > 2) {
      score += 0.2
      reasons.push('Contains excessive punctuation')
    }

    // Check for all caps content
    const capsRatio = (textContent.match(/[A-Z]/g) || []).length / textContent.length
    if (capsRatio > 0.5 && textContent.length > 20) {
      score += 0.3
      reasons.push('Contains excessive capital letters')
    }

    // Check for repeated characters
    const repeatedChars = textContent.match(/(.)\1{4,}/g)
    if (repeatedChars && repeatedChars.length > 0) {
      score += 0.2
      reasons.push('Contains repeated character patterns')
    }

    // Check for gibberish (simple heuristic)
    const words = textContent.split(/\s+/)
    const shortWords = words.filter(word => word.length < 3).length
    const longWords = words.filter(word => word.length > 15).length
    const gibberishRatio = (shortWords + longWords) / Math.max(words.length, 1)
    
    if (gibberishRatio > 0.7) {
      score += 0.4
      reasons.push('Content appears to be gibberish')
    }

    return { score: Math.min(score, 1), reasons }
  }

  const checkUserAgent = (): { isBlocked: boolean; reason?: string } => {
    const userAgent = navigator.userAgent.toLowerCase()
    
    for (const blockedAgent of spamConfig.blockedUserAgents) {
      if (userAgent.includes(blockedAgent.toLowerCase())) {
        return {
          isBlocked: true,
          reason: `Blocked user agent: ${blockedAgent}`
        }
      }
    }

    // Check for missing or suspicious user agent
    if (!navigator.userAgent || navigator.userAgent.length < 10) {
      return {
        isBlocked: true,
        reason: 'Missing or invalid user agent'
      }
    }

    return { isBlocked: false }
  }

  const checkHoneypot = (): { isSpam: boolean; reason?: string } => {
    if (!spamConfig.enableHoneypot) {
      return { isSpam: false }
    }

    if (honeypotValue.value && honeypotValue.value.trim() !== '') {
      return {
        isSpam: true,
        reason: 'Honeypot field was filled'
      }
    }

    return { isSpam: false }
  }

  const analyzeSpam = (formData: Record<string, any>): SpamAnalysisResult => {
    const reasons: string[] = []
    const warnings: string[] = []
    let totalScore = 0
    let blockSubmission = false

    // Check honeypot
    const honeypotResult = checkHoneypot()
    if (honeypotResult.isSpam) {
      blockSubmission = true
      reasons.push(honeypotResult.reason!)
    }

    // Check user agent
    const userAgentResult = checkUserAgent()
    if (userAgentResult.isBlocked) {
      blockSubmission = true
      reasons.push(userAgentResult.reason!)
    }

    // Check rate limiting
    if (isRateLimited.value) {
      blockSubmission = true
      reasons.push('Rate limit exceeded')
    }

    // Analyze timing
    const timingResult = analyzeFormTiming()
    if (!timingResult.isValid) {
      totalScore += 0.3
      reasons.push(...timingResult.reasons)
    }

    // Analyze behavior
    const behaviorResult = analyzeBehavior()
    totalScore += behaviorResult.score
    if (behaviorResult.score > 0.5) {
      reasons.push(...behaviorResult.reasons)
    } else if (behaviorResult.score > 0.2) {
      warnings.push(...behaviorResult.reasons)
    }

    // Analyze content
    const contentResult = analyzeContent(formData)
    totalScore += contentResult.score
    if (contentResult.score > 0.4) {
      reasons.push(...contentResult.reasons)
    } else if (contentResult.score > 0.2) {
      warnings.push(...contentResult.reasons)
    }

    // Determine if it's spam
    const isSpam = totalScore > 0.7 || blockSubmission
    const confidence = Math.min(totalScore, 1)

    return {
      isSpam,
      confidence,
      reasons,
      warnings,
      blockSubmission
    }
  }

  const recordSubmissionAttempt = () => {
    submissionAttempts.value++
    lastSubmissionTime.value = Date.now()
  }

  const generateHoneypotField = () => {
    return {
      name: 'website_url', // Common honeypot field name
      id: 'website_url',
      type: 'text',
      value: honeypotValue.value,
      style: 'position: absolute; left: -9999px; opacity: 0; pointer-events: none;',
      tabIndex: -1,
      autoComplete: 'off',
      'aria-hidden': 'true'
    }
  }

  const getSpamProtectionData = () => {
    return {
      honeypot: honeypotValue.value,
      submit_time: Date.now() - formStartTime.value,
      user_agent: navigator.userAgent,
      keystrokes: keystrokes.value,
      mouse_movements: mouseMovements.value,
      focus_events: focusEvents.value,
      paste_events: pasteEvents.value,
      timestamp: Date.now()
    }
  }

  const resetSpamProtection = () => {
    formStartTime.value = Date.now()
    honeypotValue.value = ''
    resetBehaviorTracking()
  }

  onMounted(() => {
    initializeSpamProtection()
  })

  return {
    // State
    isRateLimited,
    honeypotValue,
    behaviorScore,
    
    // Methods
    initializeSpamProtection,
    analyzeSpam,
    recordSubmissionAttempt,
    generateHoneypotField,
    getSpamProtectionData,
    resetSpamProtection,
    
    // Analysis methods
    analyzeFormTiming,
    analyzeBehavior,
    analyzeContent,
    checkUserAgent,
    checkHoneypot
  }
}