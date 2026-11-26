import { Globe } from 'lucide-react';
import { useI18n } from '@/i18n';

interface LanguageSwitcherProps {
  compact?: boolean;
}

export default function LanguageSwitcher({ compact = false }: LanguageSwitcherProps) {
  const { locale, setLocale } = useI18n();

  return (
    <div
      className="inline-flex items-center bg-[#f4f6f5] p-0.5 rounded-full border border-gray-200/80 shadow-2xs select-none"
      role="group"
      aria-label={locale === 'en' ? 'Switch Language' : 'Ubah Bahasa'}
    >
      {!compact && (
        <span
          className="pl-2 pr-1 text-gray-400 flex items-center"
          title={locale === 'en' ? 'Language' : 'Bahasa'}
        >
          <Globe className="w-3.5 h-3.5 text-[#0067AC]" />
        </span>
      )}
      <button
        type="button"
        onClick={() => setLocale('en')}
        className={`px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide transition cursor-pointer ${
          locale === 'en' ? 'bg-[#0067AC] text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800'
        }`}
        title="English"
      >
        EN
      </button>
      <button
        type="button"
        onClick={() => setLocale('id')}
        className={`px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide transition cursor-pointer ${
          locale === 'id' ? 'bg-[#0067AC] text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800'
        }`}
        title="Bahasa Indonesia"
      >
        ID
      </button>
    </div>
  );
}
