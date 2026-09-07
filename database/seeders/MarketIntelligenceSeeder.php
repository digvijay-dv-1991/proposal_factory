<?php

namespace Database\Seeders;

use App\Models\ContractVehicle;
use App\Models\IndustryEvent;
use App\Models\MarketCompetitor;
use App\Models\MarketPartner;
use Illuminate\Database\Seeder;

/**
 * Seeds the four static market-intelligence datasets found in the
 * reference app (contractVehicleCatalog, priorityCompetitorAnalysis,
 * alqimiPartnerCatalog, technologyEvents) — hand-curated ALQIMI content,
 * not derived from opportunity records.
 */
class MarketIntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedContractVehicles();
        $this->seedCompetitors();
        $this->seedPartners();
        $this->seedIndustryEvents();
    }

    private function seedContractVehicles(): void
    {
        ContractVehicle::query()->insert([
            [
                'name' => 'CIO-SP3',
                'full_name' => 'Chief Information Officer – Solutions and Partners 3',
                'agency' => 'NIH Information Technology Acquisition and Assessment Center (NITAAC)',
                'type' => 'Government-Wide Acquisition Contract (GWAC)',
                'status' => 'ALQIMI Contract Vehicle',
                'description' => 'CIO-SP3 is a government-wide IT services GWAC administered by NITAAC. It supports federal agencies with streamlined ordering across a broad range of IT services and solutions and is organized around multiple task areas and labor categories. The vehicle is designed for complex IT requirements spanning health, biomedical, enterprise infrastructure, software, digital government, cybersecurity, integration, and related professional IT services.',
                'alqimi_use' => 'Useful for ALQIMI data engineering, AOSEN, AI/ML, modernization, health IT, analytics, integration, software-development, and mission-technology services when an agency can buy through NITAAC.',
                'url' => 'https://nitaac.nih.gov/gwacs/cio-sp3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'GSA Multiple Award Schedule',
                'full_name' => 'GSA MAS / Federal Supply Schedule',
                'agency' => 'U.S. General Services Administration',
                'type' => 'Multiple Award Schedule (MAS)',
                'status' => 'ALQIMI Contract Vehicle',
                'description' => 'The GSA Multiple Award Schedule is a long-term government-wide contracting vehicle that gives federal buyers access to commercial products, services, and solutions at negotiated terms and prices. MAS offerings are organized into large categories, subcategories, and Special Item Numbers, allowing agencies to compete orders among qualified Schedule contractors.',
                'alqimi_use' => 'Provides a broad acquisition path for ALQIMI professional services, data and analytics, software, cloud, AI/ML, modernization, cybersecurity-adjacent technology services, and other offerings covered by ALQIMI\'s awarded Schedule scope.',
                'url' => 'https://www.gsa.gov/buy-through-us/purchasing-programs/multiple-award-schedule',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'NASA SEWP VI',
                'full_name' => 'Solutions for Enterprise-Wide Procurement VI',
                'agency' => 'National Aeronautics and Space Administration',
                'type' => 'Government-Wide Acquisition Contract (GWAC)',
                'status' => 'ALQIMI Contract Vehicle',
                'description' => 'NASA SEWP VI is the next generation of NASA\'s government-wide acquisition vehicle for federal technology procurement. NASA describes SEWP as a GWAC for Information Technology, Communications and Audio Visual products and services for federal agencies and approved contractors; SEWP VI expands the vehicle for the next acquisition period and is currently being onboarded following award activity.',
                'alqimi_use' => 'Strong fit for ALQIMI software platforms, data and AI solutions, cloud and infrastructure-related technology, cybersecurity-supporting technology, engineering, consulting, and other mission-focused IT requirements that can be acquired through SEWP VI.',
                'url' => 'https://sewp.nasa.gov/sewpvi/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedCompetitors(): void
    {
        MarketCompetitor::query()->insert([
            [
                'name' => 'CENTRA',
                'url' => 'https://www.amentum.com/markets/intelligence/',
                'label' => 'Intelligence Analysis · National Security · Mission Support',
                'alqimi_products' => 'FORGE ARGUS · FORGE ARC · FORGE VANGUARD · AOSEN Sovereign Data Fabric · ALQIMI Services',
                'competitor_offering' => 'CENTRA was acquired by PAE and is now represented within Amentum. The current Amentum intelligence portfolio includes all-source intelligence, OSINT, multi-INT analytics, systems engineering, secure communications, data analytics, AI/ML, and mission support.',
                'overlap' => 'High overlap in intelligence analysis, OSINT, data fusion, AI/ML-enabled analytics, mission decision support, systems integration, and national-security customer environments.',
                'alqimi_advantage' => 'ALQIMI can differentiate with focused commercial software products, faster configurable mission applications, specialized PAI/OSINT products, sovereign data-fabric architecture, and a smaller-company ability to tailor solutions rapidly.',
                'competitor_advantage' => 'Amentum/CENTRA brings scale, a large cleared workforce, extensive IC/DoD past performance, all-source and multi-INT analytic depth, systems engineering, secure communications, and broad mission-support capacity.',
                'strategy' => 'Avoid competing on staffing scale. Position ALQIMI as the agile product-and-data layer: faster deployment, configurable software, specialized intelligence products, and focused modernization where customers want an alternative to a large services integrator.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exiger',
                'url' => 'https://www.exiger.com/',
                'label' => 'Supply Chain Risk / FOCI / Third-Party Risk',
                'alqimi_products' => 'FORGE IRON VEIL · Identity Intelligence · Persistent Stare · AOSEN',
                'competitor_offering' => 'Exiger platform: multi-tier supply-chain mapping, supplier/product/part visibility, risk and compliance, due diligence, and software supply-chain security.',
                'overlap' => 'High overlap in supply-chain risk, ownership/control analysis, supplier intelligence, due diligence, entity relationships, persistent risk monitoring, and government national-security use cases.',
                'alqimi_advantage' => 'ALQIMI can differentiate where the customer needs mission-specific intelligence workflows, OSINT/PAI enrichment, configurable operational applications, broader data-fabric integration, and analyst-facing decision support beyond a dedicated SCRM platform.',
                'competitor_advantage' => 'Exiger has a highly specialized, mature SCRM platform, deep supplier/part data, multi-tier supply-chain visibility, compliance workflows, and strong public positioning in supplier risk management.',
                'strategy' => 'Do not compete as a generic SCRM database. Position ALQIMI around mission integration, intelligence enrichment, configurable workflows, FOCI/entity analysis, and use cases where SCRM data must be fused with operational or intelligence data.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Knowesis',
                'url' => 'https://www.knowesis-inc.com/',
                'label' => 'DHS CFIUS · Entity Risk Assessment · Professional Services',
                'alqimi_products' => 'FORGE IRON VEIL · Identity Intelligence · Persistent Stare · AOSEN · FORGE VANGUARD · ALQIMI Services',
                'competitor_offering' => 'Knowesis supports DHS CISA National Risk Management Center and its Foreign Investment Risk Branch with professional services for CFIUS-related entity risk assessment. Publicly described work includes developing risk-mitigation plans for foreign investments, assessing adversarial influence through private entities, supporting acquisition safeguards, restricting or removing risky IT hardware/software/services from federal networks, and reviewing entities for sensitive information-sharing programs.',
                'overlap' => 'Very high mission overlap with ALQIMI in FOCI/CFIUS, foreign-ownership and influence analysis, entity risk, supply-chain and vendor risk, acquisition risk, beneficial-ownership research, mitigation planning, and decision support for national-security stakeholders. The primary competitive distinction is delivery model: Knowesis is strongly positioned as a professional-services assessment provider, while ALQIMI can pair analytic services with reusable software, data, and persistent-monitoring products.',
                'alqimi_advantage' => 'ALQIMI can combine FORGE IRON VEIL, Identity Intelligence, Persistent Stare, AOSEN, and FORGE VANGUARD to automate and operationalize large portions of the CFIUS/FOCI workflow: entity resolution, beneficial-ownership and relationship mapping, foreign-affiliation research, supply-chain intelligence, persistent change detection, curated data ingestion, alerting, case workflow, and analyst decision support. This provides a path from one-time assessment services to a continuously updated risk picture.',
                'competitor_advantage' => 'Knowesis has direct, publicly documented DHS CISA NRMC/FIRB experience supporting CFIUS and Entity Risk Assessments. That customer intimacy, mission-specific methodology, assessment-writing experience, and established professional-services delivery are major advantages when the requirement emphasizes experienced analysts, policy/program support, risk-mitigation plans, and CFIUS process expertise.',
                'strategy' => 'Treat Knowesis as a direct competitor for DHS CFIUS/FOCI professional-services work. Do not position ALQIMI only as an analytics platform. Pair experienced FOCI/CFIUS analysts and professional services with FORGE IRON VEIL and AOSEN, emphasizing faster research, repeatable entity-risk workflows, traceable evidence, persistent monitoring, and the ability to turn analyst methodology into an operational software-enabled capability.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Palantir',
                'url' => 'https://www.palantir.com/',
                'label' => 'Data Platform · AI · Defense / Intelligence',
                'alqimi_products' => 'AOSEN · AOSEN IQ · AOSEN Sovereign Data Fabric · FORGE ARC · FORGE ARGUS · FORGE Single App',
                'competitor_offering' => 'Palantir Foundry provides data integration and operations, Gotham supports defense and intelligence decision workflows, AIP provides governed generative-AI/agent capabilities, and Apollo supports software deployment across complex environments.',
                'overlap' => 'Very high overlap in enterprise data integration, data fabrics, operational analytics, ontology/knowledge models, AI/LLM enablement, defense and intelligence workflows, mission applications, and decision support.',
                'alqimi_advantage' => 'ALQIMI can differentiate with a more focused and modular product set, faster tailoring to mission-specific requirements, lighter-weight deployments, specialized PAI/OSINT offerings, and a lower-friction alternative where customers do not need a large enterprise platform.',
                'competitor_advantage' => 'Palantir has mature, deeply integrated platforms, broad defense and intelligence adoption, extensive data/AI capabilities, strong ontology-driven workflows, and the ability to deploy across classified and tactical environments.',
                'strategy' => 'Avoid a head-on platform breadth comparison. Position ALQIMI around speed, modularity, mission-specific tailoring, specialized data products, interoperability, and targeted deployments where a smaller, configurable solution is preferable.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedPartners(): void
    {
        MarketPartner::query()->insert([
            [
                'name' => 'Aeris',
                'url' => 'https://www.aeris.com/',
                'label' => 'Secure Cellular IoT · Device Connectivity · IoT Security',
                'what_they_do' => 'Aeris provides secure cellular IoT platforms for global connectivity, eSIM and device lifecycle management, real-time monitoring, automation, and IoT security. Its IoT Accelerator and Watchtower offerings are designed to connect, manage, monitor, and protect large fleets of connected devices.',
                'client_alignment' => 'Best ALQIMI client alignment: DoD operational environments, Air Force and Army edge missions, installation and infrastructure programs, logistics and fleet use cases, and health programs that depend on connected devices or remote monitoring. This is a strategic-fit assessment rather than a statement that Aeris currently supports those ALQIMI customers.',
                'product_areas' => 'AOSEN Edge / DDIL Data Services · AOSEN Sovereign Data Fabric · FORGE ARGUS · FORGE VANGUARD · Health Sentinel',
                'partnership_value' => 'Aeris can provide the secure device-connectivity and IoT-management layer while ALQIMI supplies data ingestion, normalization, fusion, analytics, persistent monitoring, geospatial awareness, and operational decision support.',
                'use_together' => 'Use the partnership where a mission requires fielded or distributed connected devices whose telemetry must be securely moved into ALQIMI data fabrics and converted into actionable operational intelligence.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'AWS',
                'url' => 'https://aws.amazon.com/federal/',
                'label' => 'Federal Cloud · GovCloud · AI/ML · Data & Modernization',
                'what_they_do' => 'Amazon Web Services provides cloud infrastructure, data, analytics, AI/ML, application modernization, security, and government-specific cloud environments. AWS GovCloud supports sensitive U.S. government workloads with compliance-oriented isolated regions, while AWS also offers services such as Bedrock, SageMaker, ECS, EKS, Lambda, storage, databases, and migration tooling.',
                'client_alignment' => 'Strong ALQIMI client alignment across DoD, Defense Health Agency, federal health, civilian agencies, intelligence and national-security customers, and modernization programs that require FedRAMP, DoD impact-level, or other government cloud controls.',
                'product_areas' => 'AWS-Aligned Services · AOSEN · AOSEN IQ · AOSEN Sovereign Data Fabric · TeamHealth Clinical Data Fabric · FORGE Single App · Rhino AI',
                'partnership_value' => 'AWS provides secure hyperscale infrastructure and native cloud/AI services; ALQIMI contributes mission data architecture, governed data fabrics, application delivery, analytics, AI-enabled workflows, integration, and customer-specific operational solutions.',
                'use_together' => 'Use AWS as the secure cloud foundation underneath ALQIMI modernization, data-fabric, AI, health-data, and mission-application offerings, including GovCloud patterns for regulated government workloads.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Garud',
                'url' => 'https://www.garudtechnology.com/',
                'label' => 'Federal Mission Support · SETA · Engineering · Test & Evaluation',
                'what_they_do' => 'Garud Technology Services provides technical and professional services to U.S. government customers, including systems engineering and technical assistance, mission support, test and evaluation, business performance optimization, research and development support, enterprise acquisition support, CIO services, and workflow/process modernization.',
                'client_alignment' => 'Strong ALQIMI customer alignment with DHS, FEMA, CBP, DoD and Air National Guard environments. Garud also supports federal civilian organizations including NOAA and other agencies where mission support, engineering, acquisition, and modernization intersect with ALQIMI data and analytics capabilities.',
                'product_areas' => 'AOSEN · AOSEN Enterprise Architecture · FORGE VANGUARD · FORGE ARGUS · RAAVN · ALQIMI Services / AOSEN Accelerators',
                'partnership_value' => 'Garud contributes customer access, mission-domain SMEs, SETA, systems engineering, acquisition support, test and evaluation, and program execution. ALQIMI adds data platforms, analytics, AI, OSINT/PAI, persistent monitoring, modernization, and software-enabled decision support.',
                'use_together' => 'Use the partnership when the customer needs both deep federal mission/program support and an operational data/AI capability, especially DHS, CBP, FEMA, DoD, test-and-evaluation, or engineering-heavy pursuits.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PSI',
                'url' => 'https://www.aboutpsi.com/',
                'label' => 'Federal Health · Software Development · DevSecOps · Data Intelligence',
                'what_they_do' => 'Planned Systems International (PSI) is a federal technology and services company focused on full-lifecycle software development, mission-critical system sustainment, secure DevSecOps, health IT, data intelligence, modernization, data management and interoperability, systems integration, and program support.',
                'client_alignment' => 'Very strong ALQIMI customer overlap with Defense Health Agency and the Military Health System, Department of Veterans Affairs, Air Force, Army, DHS, Navy, DIA, DLA, USSOCOM, and other federal customers. PSI publicly highlights deep DHA and VA work, including clinical systems, purchased-care applications, data, modernization, and benefits-program support.',
                'product_areas' => 'HERMES · TeamHealth Clinical Data Fabric · TED.ai · AOSEN · AOSEN Data Management · AOSEN IQ · ALQIMI Services / AOSEN Accelerators',
                'partnership_value' => 'PSI brings large-scale federal delivery, software sustainment, DevSecOps, health-IT program depth, system integration, and customer past performance. ALQIMI contributes productized data fabrics, analytics, AI/ML, document/data exploitation, and mission-specific data products that can strengthen PSI-led services programs.',
                'use_together' => 'Use PSI where ALQIMI needs a larger delivery partner or strong incumbent-style customer credentials in DHA, VA, federal health, defense IT, or complex sustainment programs while preserving an ALQIMI product and data-platform role.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rhino.AI',
                'url' => 'https://www.rhino.ai/',
                'label' => 'Legacy Modernization · Business Logic Extraction · AI-Ready Transformation',
                'what_they_do' => 'rhino.ai extracts, structures, and governs business logic from legacy code, SaaS, COTS, mainframe, workflows, and documents. It creates a traceable system of record for enterprise logic that can drive modernization and ground AI agents. rhino.ai is also an AWS partner and positions its platform as a modernization discovery and logic layer for AWS transformations.',
                'client_alignment' => 'Best ALQIMI client alignment: federal agencies with complex legacy portfolios, including DoD, DHA, VA, DHS, and civilian-agency modernization programs where business rules, application logic, legacy workflows, and system dependencies must be understood before migration or replacement.',
                'product_areas' => 'Rhino AI · AOSEN · AOSEN IQ · AOSEN Enterprise Architecture · Project INSIGHTS · AWS-Aligned Services',
                'partnership_value' => 'rhino.ai provides automated discovery and structured extraction of hidden application and business logic. ALQIMI can connect that output to enterprise data architecture, data migration, knowledge extraction, AI readiness, governed data services, workflow modernization, and AWS deployment.',
                'use_together' => 'Use the partnership for legacy-to-modern transformation where the hardest problem is understanding existing business logic before data migration, replatforming, application redesign, or AI-agent enablement.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedIndustryEvents(): void
    {
        $events = [
            ['id' => 'EVT-TNA26', 'name' => 'AFCEA TechNet Augusta 2026', 'start' => '2026-08-17', 'end' => '2026-08-20', 'location' => 'Augusta, Georgia', 'host' => 'AFCEA International / U.S. Army Cyber Center of Excellence', 'type' => 'Defense & Federal', 'class' => 'event-defense', 'summary' => 'Army and industry conference focused on C2, counter-C2, cyber, data at the tactical edge, interoperability, AI, and unified land operations.', 'url' => 'https://events.afcea.org/Augusta26/Public/enter.aspx'],
            ['id' => 'EVT-INSS26', 'name' => 'Intelligence and National Security Summit', 'start' => '2026-08-26', 'end' => '2026-08-26', 'location' => 'Bethesda, Maryland', 'host' => 'AFCEA International / Intelligence community partners', 'type' => 'Defense & Federal', 'class' => 'event-defense', 'summary' => 'Government and industry discussions covering intelligence, national security, cyber, emerging technology, and mission partnerships.', 'url' => 'https://www.afcea.org/events'],
            ['id' => 'EVT-TIP26', 'name' => 'AFCEA TechNet Indo-Pacific 2026', 'start' => '2026-10-26', 'end' => '2026-10-29', 'location' => 'Honolulu, Hawaii', 'host' => 'AFCEA International / U.S. Indo-Pacific Command community', 'type' => 'Defense & Federal', 'class' => 'event-defense', 'summary' => 'Government, military, academia, and industry event focused on AI, cyber resilience, data, C5ISRT, influence, and decision superiority.', 'url' => 'https://events.afcea.org/tip26/Public/enter.aspx'],
            ['id' => 'EVT-FLIT26', 'name' => 'Florida IT Leadership Forum 2026', 'start' => '2026-12-01', 'end' => '2026-12-01', 'location' => 'Florida', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Public-sector technology leadership event connecting state and local executives with technology providers and government modernization partners.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-ENV26', 'name' => 'Envision: A State and Local Government IT Summit 2026', 'start' => '2026-12-02', 'end' => '2026-12-03', 'location' => 'United States — see organizer', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'State and local government technology summit focused on public-sector modernization, digital services, data, cybersecurity, and emerging technology.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-HIC26', 'name' => 'Hawaii Public Sector Cybersecurity Summit 2026', 'start' => '2026-12-02', 'end' => '2026-12-02', 'location' => 'Hawaii', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Cybersecurity event for state, local, education, and public-sector technology leaders and their industry partners.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-MDAI26', 'name' => 'Maryland K-12 AI Leadership Conference 2026', 'start' => '2026-12-03', 'end' => '2026-12-03', 'location' => 'Maryland', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Public education and technology conference focused on responsible AI adoption, leadership, data governance, and implementation.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-WIDG26', 'name' => 'Wisconsin Digital Government Summit 2026', 'start' => '2026-12-03', 'end' => '2026-12-03', 'location' => 'Wisconsin', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Government and contractor event focused on digital government, cybersecurity, data, constituent services, and technology modernization.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-GOVAI26', 'name' => 'GovAI Coalition Summit 2026', 'start' => '2026-12-09', 'end' => '2026-12-10', 'location' => 'United States — see organizer', 'host' => 'GovAI Coalition / Government Technology', 'type' => 'Government AI', 'class' => 'event-industry', 'summary' => 'Government-focused AI event covering responsible adoption, policy, data governance, procurement, implementation, and public-sector use cases.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-NYCTF26', 'name' => 'New York City Technology Forum 2026', 'start' => '2026-12-10', 'end' => '2026-12-11', 'location' => 'New York City, New York', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Technology forum connecting New York public-sector leaders with contractors and technology companies supporting digital government.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-NVDG26', 'name' => 'Nevada Digital Government Summit 2026', 'start' => '2026-12-10', 'end' => '2026-12-10', 'location' => 'Nevada', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'State and local government technology conference focused on modernization, cyber, data, AI, and public services.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-PADG26', 'name' => 'Pennsylvania Digital Government Summit 2026', 'start' => '2026-12-10', 'end' => '2026-12-10', 'location' => 'Pennsylvania', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Public-sector and industry conference covering government technology strategy, cybersecurity, data, digital services, and innovation.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-SFLDG26', 'name' => 'Southern Florida Digital Government Summit 2026', 'start' => '2026-12-17', 'end' => '2026-12-17', 'location' => 'South Florida', 'host' => 'Government Technology', 'type' => 'State & Local', 'class' => 'event-public', 'summary' => 'Regional government technology event connecting public officials with contractors and technology providers supporting state and local missions.', 'url' => 'https://events.govtech.com/'],
            ['id' => 'EVT-IBMAAS26', 'name' => 'IBM at Agentic AI Summit 2026', 'start' => '2026-08-01', 'end' => '2026-08-03', 'location' => 'Berkeley, California and virtual', 'host' => 'Berkeley RDI / Responsible Decentralized Intelligence Foundation; IBM participating', 'type' => 'Industry / Government AI', 'class' => 'event-industry', 'summary' => 'Agentic AI research and deployment summit spanning foundation models, agent frameworks, evaluation, infrastructure, safety, security, and real-world applications, with IBM technical leadership participating.', 'url' => 'https://research.ibm.com/events/agentic-ai-summit-2026'],
            ['id' => 'EVT-IBMSEC26', 'name' => 'IBM at USENIX Security 2026', 'start' => '2026-08-12', 'end' => '2026-08-14', 'location' => 'Baltimore, Maryland', 'host' => 'USENIX; IBM Research participating', 'type' => 'Cybersecurity', 'class' => 'event-industry', 'summary' => 'Major U.S. cybersecurity and privacy conference for researchers, practitioners, government technologists, and industry security teams, with IBM Research participation.', 'url' => 'https://www.usenix.org/conference/usenixsecurity26'],
            ['id' => 'EVT-NAVYGOLD26', 'name' => 'Department of the Navy Gold Coast 2026', 'start' => '2026-08-17', 'end' => '2026-08-20', 'location' => 'San Diego, California', 'host' => 'NDIA San Diego / Department of the Navy OSBP; RTX outreach participant', 'type' => 'Defense Procurement & Technology', 'class' => 'event-defense', 'summary' => 'Premier Navy procurement and technology expo connecting Department of the Navy acquisition organizations, large primes, small businesses, and technology suppliers. RTX identifies this event in its 2026 supplier outreach calendar.', 'url' => 'https://www.navygoldcoast.org/'],
            ['id' => 'EVT-AFASC26', 'name' => 'Air, Space & Cyber Conference 2026', 'start' => '2026-09-14', 'end' => '2026-09-16', 'location' => 'National Harbor, Maryland', 'host' => 'Air & Space Forces Association; major defense contractors participating', 'type' => 'Defense & Federal', 'class' => 'event-defense', 'summary' => 'Air Force, Space Force, and defense-industry conference with technology exhibitions and sessions on battle management, space, cyber, AI, communications, and mission systems. RTX lists the conference in its supplier outreach calendar.', 'url' => 'https://www.afa.org/air-space-cyber-conference/'],
            ['id' => 'EVT-AUSA26', 'name' => 'AUSA Annual Meeting & Exposition 2026', 'start' => '2026-10-12', 'end' => '2026-10-14', 'location' => 'Washington, District of Columbia', 'host' => 'Association of the United States Army; Lockheed Martin, RTX, Northrop Grumman, IBM and other federal contractors typically participate', 'type' => 'Defense & Federal', 'class' => 'event-defense', 'summary' => 'Large U.S. Army and defense-industry exposition connecting Army leadership, acquisition organizations, policymakers, international delegations, and more than 750 technology exhibitors.', 'url' => 'https://meetings.ausa.org/annual/2026/'],
            ['id' => 'EVT-IBMTX26', 'name' => 'IBM TechXchange 2026', 'start' => '2026-10-26', 'end' => '2026-10-29', 'location' => 'Atlanta, Georgia', 'host' => 'IBM', 'type' => 'Contractor-Hosted Technology', 'class' => 'event-industry', 'summary' => 'IBM-hosted hands-on conference covering AI applications, data management, hybrid cloud, infrastructure, cybersecurity, governance, automation, and technical certifications. Government and public-sector registration is offered.', 'url' => 'https://www.ibm.com/events/techxchange'],
            ['id' => 'EVT-AWSRI26', 'name' => 'AWS re:Invent 2026', 'start' => '2026-11-30', 'end' => '2026-12-04', 'location' => 'Las Vegas, Nevada', 'host' => 'Amazon Web Services', 'type' => 'Contractor-Hosted Technology', 'class' => 'event-industry', 'summary' => 'AWS-hosted cloud and AI conference with technical sessions, hands-on labs, partner engagement, public-sector content, security, data, DevOps, infrastructure, and emerging technology.', 'url' => 'https://aws.amazon.com/events/'],
        ];

        foreach ($events as $event) {
            IndustryEvent::query()->create([
                'external_id' => $event['id'],
                'name' => $event['name'],
                'starts_on' => $event['start'],
                'ends_on' => $event['end'],
                'location' => $event['location'],
                'host' => $event['host'],
                'type' => $event['type'],
                'class_name' => $event['class'],
                'summary' => $event['summary'],
                'url' => $event['url'],
            ]);
        }
    }
}
