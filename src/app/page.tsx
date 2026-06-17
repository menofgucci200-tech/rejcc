import { Hero } from "@/components/sections/Hero";
import { About } from "@/components/sections/About";
import { Stats } from "@/components/sections/Stats";
import { WhyJoin } from "@/components/sections/WhyJoin";
import { Values } from "@/components/sections/Values";
import { DomainsPreview } from "@/components/sections/DomainsPreview";
import { HowToJoin } from "@/components/sections/HowToJoin";
import { Testimonials } from "@/components/sections/Testimonials";
import { Events } from "@/components/sections/Events";
import { News } from "@/components/sections/News";
import { CtaBand } from "@/components/sections/CtaBand";

export default function Home() {
  return (
    <>
      <Hero />
      <About />
      <Stats />
      <WhyJoin />
      <Values />
      <DomainsPreview />
      <HowToJoin />
      <Testimonials />
      <Events />
      <News />
      <CtaBand />
    </>
  );
}
