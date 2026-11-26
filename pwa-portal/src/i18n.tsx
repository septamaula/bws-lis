import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

export type Locale = 'en' | 'id';

const STORAGE_KEY = 'bws_lis_locale';

function getInitialLocale(): Locale {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'en' || saved === 'id') {
      return saved;
    }
  } catch {
    // ignore storage errors
  }
  return 'en';
}

interface I18nContextValue {
  locale: Locale;
  isEn: boolean;
  isId: boolean;
  setLocale: (lang: Locale) => void;
  toggleLocale: () => void;
  l: (en: string, id?: string, params?: Record<string, string | number>) => string;
}

const I18nContext = createContext<I18nContextValue | undefined>(undefined);

function interpolate(str: string, params?: Record<string, string | number>): string {
  if (!params) return str;
  return str.replace(/\{(\w+)\}/g, (_, k) => (params[k] !== undefined ? String(params[k]) : `{${k}}`));
}

export function I18nProvider({ children }: { children: React.ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>(getInitialLocale);

  useEffect(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale;
    }
  }, [locale]);

  const setLocale = useCallback((lang: Locale) => {
    if (lang !== 'en' && lang !== 'id') return;
    setLocaleState(lang);
    try {
      localStorage.setItem(STORAGE_KEY, lang);
    } catch {
      // ignore storage errors
    }
    if (typeof document !== 'undefined') {
      document.documentElement.lang = lang;
    }
  }, []);

  const toggleLocale = useCallback(() => {
    setLocale(locale === 'en' ? 'id' : 'en');
  }, [locale, setLocale]);

  const l = useCallback(
    (en: string, id?: string, params?: Record<string, string | number>) => {
      const raw = locale === 'id' ? (id ?? en) : en;
      return interpolate(raw, params);
    },
    [locale]
  );

  return (
    <I18nContext.Provider
      value={{
        locale,
        isEn: locale === 'en',
        isId: locale === 'id',
        setLocale,
        toggleLocale,
        l,
      }}
    >
      {children}
    </I18nContext.Provider>
  );
}

export function useI18n(): I18nContextValue {
  const ctx = useContext(I18nContext);
  if (!ctx) {
    throw new Error('useI18n must be used within an I18nProvider');
  }
  return ctx;
}
