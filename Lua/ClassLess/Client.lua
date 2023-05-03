local AIO = AIO or require("AIO")

-- CLIENT SECRET
local clientSecret = ""
local handlerName = "yQ4CiWjHET"

--|TInterface/ICONS/VAS_RaceChange:35:35|t
local LastContainerNum = 1
-- Settings
local iconSize = 36	
local leftConst = 125
local itemsPerRow = 6

local prices = {}

-- Ready-to-use for the system
local classes = {"Death Knight", "Druid", "Hunter", "Mage", "Paladin", "Priest", "Rogue", "Shaman", "Warlock", "Warrior", "MONK", "DEMONHUNTER"};
--Data
if AIO.IsServer() then
    --Spells
    AIO.AddAddon("lua_scripts\\ClassLess\\data\\spells.data", "spells")
    --Talents
    AIO.AddAddon("lua_scripts\\ClassLess\\data\\talents.data", "talents")
    --Locks
    AIO.AddAddon("lua_scripts\\ClassLess\\data\\locks.data", "locks")
    --Requirements
    AIO.AddAddon("lua_scripts\\ClassLess\\data\\req.data", "req")
end

if AIO.AddAddon() then
    return
end

--Variables
local spellsplus, spellsminus = {}, {}
local tpellsplus, tpellsminus = {}, {}
local talentsplus, talentsminus = {}, {}
local db = CLDB

--Functions
local function FrameToggle(frame)
    local f = _G[frame]
    if f ~= nil then
        if f:IsVisible() ~= 1 then
            f:Show()
        elseif f:IsVisible() == 1 then
            f:Hide()
        end
    end
end

local function FrameShow(fname)
    local frame = fname
    if type(fname) ~= "table" then
        frame = _G[fname]
    end
    if frame ~= nil and frame:IsVisible() ~= 1 then
        frame:Show()
    end
end

local function FrameHide(fname)
    local frame = fname
    if type(fname) ~= "table" then
        frame = _G[fname]
    end
    if frame ~= nil and frame:IsVisible() == 1 then
        frame:Hide()
    end
end

--DoShit Function
local function DoShit()
    --Table Functions
    local function tCopy(t)
        local u = {}
        for k, v in pairs(t) do
            u[k] = v
        end
        return setmetatable(u, getmetatable(t))
    end

    local function tRemoveKey(table, key)
        for i = 1, #table do
            if table[i] == key then
                tremove(table, i)
            end
        end
    end

    local function tCompare(t1, t2)
        if #t1 ~= #t2 then
            return false
        end
        for i = 1, #t1 do
            if t1[i] ~= t2[i] then
                return false
            end
        end
        return true
    end

    local function pairsSort(t, f)
        local a = {}
        for n in pairs(t) do
            table.insert(a, n)
        end
        table.sort(a, f)
        local i = 0
        local iter = function()
            i = i + 1
            if a[i] == nil then
                return nil
            else
                return a[i], t[a[i]]
            end
        end
        return iter
    end

    --Frame Creation Functions
    local function CreateTexture(base, layer, path, blend)
        local t = base:CreateTexture(nil, layer)
        if path then
            t:SetTexture(path)
        end
        if blend then
            t:SetBlendMode(blend)
        end
        return t
    end

    local function FrameBackground(frame, background)
        local t = CreateTexture(frame, "BACKGROUND")
        t:SetPoint("TOPLEFT")
        frame.topleft = t

        t = CreateTexture(frame, "BACKGROUND")
        t:SetPoint("TOPLEFT", frame.topleft, "TOPRIGHT")
        frame.topright = t

        t = CreateTexture(frame, "BACKGROUND")
        t:SetPoint("TOPLEFT", frame.topleft, "BOTTOMLEFT")
        frame.bottomleft = t

        t = CreateTexture(frame, "BACKGROUND")
        t:SetPoint("TOPLEFT", frame.topleft, "BOTTOMRIGHT")
        frame.bottomright = t
       -- frame.topleft:SetTexture(background .. "-TopLeft")
        --frame.topright:SetTexture(background .. "-TopRight")
       -- frame.bottomleft:SetTexture(background .. "-BottomLeft")
        --frame.bottomright:SetTexture(background .. "-BottomRight")
    end

    local function FrameLayout(frame, width, height)
        local texture_height = height / (256 + 75)
        local texture_width = width / (256 + 44)

        frame:SetSize(width, height)

        local wl, wr, ht, hb = texture_width * 256, texture_width * 64, texture_height * 256, texture_height * 128

        frame.topleft:SetSize(wl, ht)
        frame.topright:SetSize(wr, ht)
        frame.bottomleft:SetSize(wl, hb)
        frame.bottomright:SetSize(wr, hb)
    end

    local function MakeButton(name, parent)
        local button = CreateFrame("Button", name, parent)
        button:SetNormalFontObject(GameFontNormal)
        button:SetHighlightFontObject(GameFontHighlight)
        button:SetDisabledFontObject(GameFontDisable)
if name == "CLButton1" or name == "CLButton2" then

local texture = button:CreateTexture()
		if name == "CLButton1" then
			texture:SetTexture "Interface\\Icons\\INV_Misc_Book_09"
		else
			texture:SetTexture "Interface\\Icons\\Ability_Marksmanship"
		end
       -- texture:SetTexCoord(0, 0.625, 0, 0.6875)
        texture:SetAllPoints(button)
        button.normal = texture
        button:SetNormalTexture(texture)

        texture = button:CreateTexture()
        if name == "CLButton1" then
			texture:SetTexture "Interface\\Icons\\INV_Misc_Book_09"
		else
			texture:SetTexture "Interface\\Icons\\Ability_Marksmanship"
		end

        texture = button:CreateTexture()
        texture:SetTexture "Interface\\Buttons\\CheckButtonHilight"
       -- texture:SetTexCoord(0, 0.625, 0, 0.6875)
        texture:SetAllPoints(button)
        button:SetHighlightTexture(texture)
		else
		 local texture = button:CreateTexture()
        texture:SetTexture "Interface\\Buttons\\UI-Panel-Button-Up"
        texture:SetTexCoord(0, 0.625, 0, 0.6875)
        texture:SetAllPoints(button)
        button.normal = texture
        button:SetNormalTexture(texture)

        texture = button:CreateTexture()
        texture:SetTexture "Interface\\Buttons\\UI-Panel-Button-Down"
        texture:SetTexCoord(0, 0.625, 0, 0.6875)
        texture:SetAllPoints(button)
        button.pushed = texture
        button:SetPushedTexture(texture)

        texture = button:CreateTexture()
        texture:SetTexture "Interface\\Buttons\\UI-Panel-Button-Highlight"
        texture:SetTexCoord(0, 0.625, 0, 0.6875)
        texture:SetAllPoints(button)
        button:SetHighlightTexture(texture)
end
       
        return button
    end

    local function MakeRankFrame(button, anchor)
        local t = CreateTexture(button, "OVERLAY", "Interface\\Textures\\border")
        t:SetSize(32, 32)
        t:SetPoint("CENTER", button, anchor)
        local fs = button:CreateFontString(nil, "OVERLAY", "GameFontNormalSmall")
        fs.texture = t
        fs:SetPoint("CENTER", t)
        return fs
    end

    local function NewButton(name, parent, size, icon, rank, a, b, c, d, empty)
        local button = CreateFrame("Button", name, parent)
        -- ItemButtonTemplate (minus Count and Slot)
        button:SetSize(size, size)
        local t = CreateTexture(button, "BORDER")
        t:SetSize(size, size)
        t:SetPoint("CENTER")
        button.texture = t
		if(empty == nil) then
			t = CreateTexture(button, nil, "Interface\\Buttons\\UI-Quickslot2")
			t:SetSize(size * 1.7, size * 1.7)
			t:SetPoint("CENTER")
			button.normal = t
			button:SetNormalTexture(t)
			t = CreateTexture(button, nil, "Interface\\Buttons\\UI-Quickslot-Depress")
			t:SetSize(size, size)
			t:SetPoint("CENTER")
			button.pushed = t
			button:SetPushedTexture(t)
			t = CreateTexture(button, nil, "Interface\\Buttons\\ButtonHilight-Square", "ADD")
			t:SetSize(size, size)
			t:SetPoint("CENTER")
			button:SetHighlightTexture(t)
			-- TalentButtonTemplate
			local texture = CreateTexture(button, "BACKGROUND", "Interface\\Buttons\\UI-EmptySlot-White")
			texture:SetSize(size * 1.7, size * 1.7)
			texture:SetPoint("CENTER")
			button.slot = texture
		end
        if rank ~= nil then
            button.rank = MakeRankFrame(button, "BOTTOMRIGHT")
        end

        if icon ~= nil then
            button.texture:SetTexture(icon)
            if a ~= nil then
                button.texture:SetTexCoord(a, b, c, d)
            end
        end
        return button
    end

    --Utility Functions
    function GetPoints(type)
        if type == "ap" then
            local ap = 10 - #db.spells
            local tap = #spellsplus
            return ap - tap, tap
        end

        if type == "tp" then
            local tp = 15 - (#db.talents + #db.tpells)
            local ttp = #talentsplus + #tpellsplus
            return tp - ttp, ttp
        end
        return 0, 0
    end
    --main Frame Functions
    local function SelectTab(tab, cname, pname, bname)
        if tab ~= 0 then
            local parent = _G[pname]
            local ltab = parent:GetAttribute("tab")

            local button, lbutton = _G[bname .. tab], _G[bname .. ltab]
            local child, lchild = _G[cname .. tab], _G[cname .. ltab]
            if lchild ~= nil then
                FrameHide(lchild)
            end
            if lbutton ~= nil then
                lbutton:SetButtonState("NORMAL")
            end
            if button ~= nil then
                button:SetButtonState("PUSHED", true)
            end
            if child ~= nil then
                FrameShow(child)
            end
            parent:SetAttribute("tab", tab)
        end
    end

    local function LearnConfirm(action, state)
        local tab = _G["CLMainFrame"]:GetAttribute("tab")
        if tab ~= nil and tab > 0 then
            if action == "Learn" and state ~= "true" then
                StaticPopup_Show("LEARN_CONFIRM")
            end
            if action == "Reset" and state ~= "true" then
                StaticPopup_Show("RESET_CONFIRM")
            end

            if action == "Learn" and state == "true" then
                if tab == 1 then
                    for i = 1, #spellsplus do
                        if not tContains(db.spells, spellsplus[i]) then
                            tinsert(db.spells, spellsplus[i])
                        end
                    end
                    for i = 1, #tpellsplus do
                        if not tContains(db.tpells, tpellsplus[i]) then
                            tinsert(db.tpells, tpellsplus[i])
                        end
                    end
                    wipe(spellsplus)
                    wipe(spellsminus)
                    wipe(tpellsplus)
                    wipe(tpellsminus)
                    sort(db.spells)
                    sort(db.tpells)
                    AIO.Handle(handlerName, "LearnSpell", db.spells, db.tpells, clientSecret)
                end

                if tab == 2 then
                    for i = 1, #talentsplus do
                        if not tContains(db.talents, talentsplus[i]) then
                            tinsert(db.talents, talentsplus[i])
                        end
                    end
                    wipe(talentsplus)
                    wipe(talentsminus)
                    sort(db.talents)
                    AIO.Handle(handlerName, "LearnTalent", db.talents, clientSecret)
                end

               
            end

            if action == "Reset" and state == "true" then
                if tab == 1 then
                    wipe(spellsplus)
                    wipe(spellsminus)
                    wipe(tpellsplus)
                    wipe(tpellsminus)
                end

                if tab == 2 then
                    wipe(talentsplus)
                    wipe(talentsminus)
                end

            end
            SelectTab(tab, "CLContainer", "CLMainFrame", "CLButton")
        end
    end

    ------Fill Spells Functions
    local function TempLearnSpell(spell, talent)
        if tContains(spellsminus, spell) then
            tRemoveKey(spellsminus, spell)
        end
        if not tContains(spellsplus, spell) then
            tinsert(spellsplus, spell)
        end
        if talent == 1 then
            if tContains(tpellsminus, spell) then
                tRemoveKey(tpellsminus, spell)
            end
            if not tContains(tpellsplus, spell) then
                tinsert(tpellsplus, spell)
            end
        end
    end

    local function TempUnlearnSpell(spell, talent)
        if tContains(spellsplus, spell) then
            tRemoveKey(spellsplus, spell)
        end
        if not tContains(spellsminus, spell) then
            tinsert(spellsminus, spell)
        end
        if talent == 1 then
            if tContains(tpellsplus, spell) then
                tRemoveKey(tpellsplus, spell)
            end
            if not tContains(tpellsminus, spell) then
                tinsert(tpellsminus, spell)
            end
        end
    end

    local function TempLearnTalent(spell, talent)
        if tContains(talentsminus, spell) then
            tRemoveKey(talentsminus, spell)
        end
        if not tContains(talentsplus, spell) then
            tinsert(talentsplus, spell)
        end
    end

    local function TempUnlearnTalent(spell, talent)
        if tContains(talentsplus, spell) then
            tRemoveKey(talentsplus, spell)
        end
        if not tContains(talentsminus, spell) then
            tinsert(talentsminus, spell)
        end
    end

    local function ParseTooltip(spell)
        local f = CreateFrame("GameTooltip", "CLTmpTooltip", UIParent, "GameTooltipTemplate")
        f:SetOwner(UIParent, "ANCHOR_NONE")
        local link = GetSpellLink(spell)
        if link == nil then
            link = GetSpellLink(78)
            link = gsub(link, "78", spell)
        end
        f:SetHyperlink(link)

        local t = {}

        for i = 1, select("#", f:GetRegions()) do
            local ttl = _G["CLTmpTooltipTextLeft" .. i]
            local ttr = _G["CLTmpTooltipTextRight" .. i]
            if (ttl ~= nil and ttl:GetText() ~= nil) and (ttr ~= nil and ttr:GetText() ~= nil) then
                tinsert(t, {ttl:GetText(), ttr:GetText()})
            elseif (ttl ~= nil and ttl:GetText() ~= nil) then
                tinsert(t, ttl:GetText())
            end
        end

        f:ClearLines()
        f:Hide()
        return t
    end

    local function ButtonTooltip(button, spell, nspell, rank, ranks, level, lcolor, ccolor, state, cost, lock, req, rreq)
        local bname = button:GetName()
        local pt = ParseTooltip(spell)
        local c = RED_FONT_COLOR
        button.tooltip =
            _G[bname .. "tooltip"] or CreateFrame("GameTooltip", bname .. "tooltip", button, "GameTooltipTemplate")

        button:SetScript(
            "OnEnter",
            function()
                button.tooltip:Hide()
                button.tooltip:SetOwner(button, "ANCHOR_RIGHT")
                local link = GetSpellLink(nspell)
                if link == nil then
                    link = GetSpellLink(78)
                    link = gsub(link, "78", nspell)
                end
                button.tooltip:SetHyperlink(link)
                button.tooltip:AddDoubleLine("Current rank", rank .. "/" .. ranks, 1, 1, 1, 1, 1, 1)
                if pt[#pt] ~= nil and rank > 0 and ranks > 1 and rank ~= ranks then
                    button.tooltip:AddLine(pt[#pt], nil, nil, nil, true)
                end

                if rreq ~= nil then
                    button.tooltip:AddLine(rreq, c.r, c.g, c.b)
                end
                if rank < ranks then
                    if req ~= nil then
                        button.tooltip:AddLine(req, c.r, c.g, c.b)
                    end
                    if lock ~= nil then
                        button.tooltip:AddLine(lock, c.r, c.g, c.b)
                    end
                    if level ~= nil then
                        button.tooltip:AddLine("Requires level " .. level, lcolor.r, lcolor.g, lcolor.b)
                    end
                    button.tooltip:AddLine(cost, ccolor.r, ccolor.g, ccolor.b)
                end
                button.tooltip:AddLine("SPELLID: " .. spell)
                button.tooltip:Show()
            end
        )

        button:SetScript(
            "OnLeave",
            function()
                button.tooltip:Hide()
            end
        )
    end

    local function FillSpells(class, spec, parent, mode)
        local spells, eqt, spellcheck

        local spellcheck1 = tCopy(db.spells)
        for i = 1, #spellsplus do
            if not tContains(spellcheck1, spellsplus[i]) then
                tinsert(spellcheck1, spellsplus[i])
            else
                tremove(spellsplus, i)
            end
        end
        for i = 1, #spellsminus do
            if tContains(spellcheck1, spellsminus[i]) then
                tRemoveKey(spellcheck1, spellsminus[i])
            else
                tremove(spellsminus, i)
            end
        end

        local spellcheck2 = tCopy(db.talents)
        for i = 1, #talentsplus do
            if not tContains(spellcheck2, talentsplus[i]) then
                tinsert(spellcheck2, talentsplus[i])
            else
                tremove(talentsplus, i)
            end
        end
        for i = 1, #talentsminus do
            if tContains(spellcheck2, talentsminus[i]) then
                tRemoveKey(spellcheck2, talentsminus[i])
            else
                tremove(talentsminus, i)
            end
        end

        local allspells = tCopy(spellcheck1)
        for i = 1, #spellcheck2 do
            if not tContains(allspells, spellcheck2[i]) then
                tinsert(allspells, spellcheck2[i])
            end
        end

        if mode == "spell" then
            spells = db.data.spells[class][spec][4]
            eqt = "false"
            if tCompare(db.spells, spellcheck1) then
                eqt = "true"
            end
            spellcheck = spellcheck1
        else
            spells = db.data.talents[class][spec][4]
            eqt = "false"
            if tCompare(db.talents, spellcheck2) then
                eqt = "true"
            end
            spellcheck = spellcheck2
        end

        local left, top = leftConst, -183

        for i = 1, #spells do
            local spellid, levelid = spells[i][1], spells[i][2]

            local prank, rank, nrank, ranks, spell, nspell, nlevel
            rank, ranks = 0, #spellid

            for j = 1, ranks do
                if tContains(spellcheck, spellid[j]) then
                    rank = j
                end
            end

            if rank > 0 then
                spell = spellid[rank]
            else
                rank = 0
                spell = spellid[1]
            end
            if rank + 1 <= ranks then
                nspell, nlevel, nrank = spellid[rank + 1], levelid[rank + 1], rank + 1
            else
                nspell, nlevel, nrank = spellid[rank], levelid[rank], rank
            end
            prank = rank - 1

            local icon = ({GetSpellInfo(nspell)})[3]
            local button =
                _G["CLSpellsClass" .. class .. "Spec" .. spec .. mode .. i] or
                NewButton("CLSpellsClass" .. class .. "Spec" .. spec .. mode .. i, parent, iconSize, icon, "true")
            button:SetButtonState("NORMAL", "true")
            button:SetPoint("TOPLEFT", left, top)
            if button:GetAttribute("hrank") == nil or eqt == "true" then
                button:SetAttribute("hrank", rank)
            end
            local hrank = button:GetAttribute("hrank")

            local state, saturated, color, acost, tcost, lock, req, rreq =
                "normal",
                0,
                GREEN_FONT_COLOR,
                1,
                0,
                nil,
                nil,
                nil
            local ap, tp = GetPoints("ap"), GetPoints("tp")
            local lcolor, ccolor = color, color
            local nacost, ntcost = acost, tcost
            local ncost

            if rank == 1 and spells[i][4] == 1 then
                tcost = 1
            end
            if nrank == 1 and spells[i][4] == 1 then
                ntcost = 1
            end

            if rank == ranks then
                state = "full"
            end

            if state ~= "full" then
                if UnitLevel("player") < nlevel then
                    state = "disabled"
                    lcolor = RED_FONT_COLOR
                end

                if mode == "spell" then
                    if ap < nacost or tp < ntcost then
                        state = "disabled"
                        ccolor = RED_FONT_COLOR
                    end
                else
                    if tp < nacost or ap < ntcost then
                        state = "disabled"
                        ccolor = RED_FONT_COLOR
                    end
                end

                if db.locks[spell] ~= nil then
                    for h = 1, #db.locks[spell] do
                        if tContains(allspells, db.locks[spell][h]) then
                            state = "disabled"
                            lock = 'Locked by "' .. ({GetSpellInfo(db.locks[spell][h])})[1] .. '" ' .. mode
                            break
                        end
                    end
                end

                if db.req[nspell] ~= nil then
                    local reqs, reqr = ({GetSpellInfo(db.req[nspell])})[1], ({GetSpellInfo(db.req[nspell])})[2]
                    if not tContains(allspells, db.req[nspell]) then
                        state = "disabled"
                        req = "req " .. mode .. ' "' .. reqs
                        if reqr ~= "" then
                            req = req .. "(" .. reqr .. ')"'
                        else
                            req = req .. '"'
                        end
                    end
                end
            end

            if db.rreq[spell] ~= nil then
                local rreqs, rreqr = ({GetSpellInfo(db.rreq[spell])})[1], ({GetSpellInfo(db.rreq[spell])})[2]
                if tContains(allspells, db.rreq[spell]) and rank ~= hrank then
                    state = "req"
                    rreq = "Required for " .. mode .. ' "' .. rreqs
                    if rreqr ~= "" then
                        rreq = rreq .. "(" .. rreqr .. ')"'
                    else
                        rreq = rreq .. '"'
                    end
                end
            end

            if state == "disabled" and rank > 0 then
                state = "temp"
            end
			
			
            if state == "disabled" then
                saturated = 1
                color = GRAY_FONT_COLOR
            elseif state == "full" then
                color = NORMAL_FONT_COLOR
            elseif state == "temp" then
                color = RAID_CLASS_COLORS["SHAMAN"]
            elseif state == "req" then
                color = ORANGE_FONT_COLOR
            end

            button.texture:SetDesaturated(saturated)
            button.slot:SetVertexColor(color.r, color.g, color.b)
            button.rank:SetVertexColor(color.r, color.g, color.b)
            button.rank:SetText(rank)

            if mode == "spell" then
                ncost = "Requires 1 AP"
                if ntcost == 1 then
                    ncost = ncost .. ", 1 TP"
                end
            else
                ncost = "Requires 1 TP"
                if ntcost == 1 then
                    ncost = ncost .. ", 1 AP"
                end
            end
	
			local clickable = true

            if state == "normal" and rank > hrank then
                if nrank <= ranks then
                    button:RegisterForClicks("LeftButtonDown", "RightButtonDown")
                end
            end
            if state == "normal" and rank == hrank then
                if nrank <= ranks then
                    button:RegisterForClicks("LeftButtonDown")
                end
            end
            if state == "normal" and rank == 0 then
                button:RegisterForClicks("LeftButtonDown")
            end
            if (state == "full" or state == "temp") and rank > hrank then
                button:RegisterForClicks("RightButtonDown")
            end
            if (state == "full" or state == "temp") and rank == hrank then
                button:RegisterForClicks()
				clickable = false
            end
            if state == "req" and rank > hrank and nrank <= ranks and rreq ~= nil then
                if UnitLevel("player") < nlevel then
                    button:RegisterForClicks()
				clickable = false
                else
                    button:RegisterForClicks("LeftButtonDown")
                end
            end
            if state == "disabled" then
                button:RegisterForClicks()
				clickable = false
            end

            button:SetScript(
                "OnClick",
                function(self, key, down)
					if not (button:IsEnabled() and clickable ~= false) then return end
					local ap, tap = GetPoints("ap")
					local tp, ttp = GetPoints("tp")
                    if key == "LeftButton" then
                        if (mode == "spell") then
							if(ap > 0 and UnitLevel("player") >= nlevel) then
								if(ntcost == 1) then
									if(tp > 0) then
									TempLearnSpell(nspell, ntcost)
									end
								else
									TempLearnSpell(nspell, ntcost)
								end
							end
                        else if mode == "talent" and tp > 0 and UnitLevel("player") >= nlevel then
                            TempLearnTalent(nspell, ntcost)
                        end
                    end
					end

                    if key == "RightButton" then
                        if mode == "spell" then
                            TempUnlearnSpell(spell, tcost)
                        else
                            TempUnlearnTalent(spell, tcost)
                        end
                    end

                    FillSpells(class, spec, parent, mode)
                    button:Hide()
                    button:Show()
                end)

            ButtonTooltip(button, spell, nspell, rank, ranks, nlevel, lcolor, ccolor, state, ncost, lock, req, rreq)

            if i / itemsPerRow == math.floor(i / itemsPerRow) then
                left = leftConst
                top = top - 50
            else
                left = left + 50
            end
        end
    end

    --Create Main Button
    local button =
        _G["CLButton"] or NewButton("CLButton", UIParent, 48, "Interface\\Tooltips\\ui_sigil_nightfae", nil, nil, nil, nil, nil, true) --Interface\\ICONS\\INV_Enchant_FormulaEpic_01
    button:SetMovable(true)
    button:EnableMouse(true)
    button:SetToplevel(true)
    button:RegisterForDrag("RightButton")
    button:SetScript("OnDragStart", button.StartMoving)
    button:SetScript("OnDragStop", button.StopMovingOrSizing)
    button:SetPoint("RIGHT", -24, 0)
    button:SetScript(
        "OnClick",
        function()
            FrameToggle("CLMainFrame")
        end
    )
	
	AIO.SavePosition(button)
	
    --button.flash = CreateFrame("Frame", "CLButtonFlash", button)
    --button.flash:SetAllPoints()
    --button.flash:Hide()
    --local texture = button.flash:CreateTexture()
   -- texture:SetTexture("Interface\\Cooldown\\star4")
   -- texture:SetAllPoints()
   -- texture:SetBlendMode("ADD")
   -- button.animation = texture:CreateAnimationGroup()
  --  local a1 = button.animation:CreateAnimation("Scale")
  --  a1:SetScale(2.5, 2.5)
  --  a1:SetDuration(3)
   -- a1:SetSmoothing("OUT")

  --  local a2 = button.animation:CreateAnimation("Rotation")
  --  a2:SetDegrees(360)
 --   a2:SetDuration(3)
   -- a2:SetSmoothing("OUT")
   -- button.animation:SetLooping("BOUNCE")
   -- button.flash:SetScript(
   --     "OnShow",
   --     function()
   --         button.animation:Play()
   --     end
  --  )
   -- button.flash:SetScript(
   --     "OnHide",
   --     function()
    --        button.animation:Stop()
    --    end
   -- )

    button.tooltip =
        _G["CLButtontooltip"] or CreateFrame("GameTooltip", "CLButtontooltip", button, "GameTooltipTemplate")
    button:SetScript(
        "OnEnter",
        function()
            local ap, tp= GetPoints("ap"), GetPoints("tp")
            button.tooltip:Hide()
            button.tooltip:SetOwner(button, "ANCHOR_RIGHT")
            button.tooltip:AddLine("Distribute your Ability or Talents Points", nil, nil, nil, true)
            local c = GREEN_FONT_COLOR
            if ap > 0 or tp > 0 then
                local string =
                    "You have\n" ..
                    ap .. " Ability Points\n" .. tp .. " Talent Points"
                button.tooltip:AddLine(string, c.r, c.g, c.b, true)
            end
            button.tooltip:AddLine("Drag with right button for move", 1, 1, 1, true)
            button.tooltip:Show()
        end
    )
    button:SetScript(
        "OnLeave",
        function()
            button.tooltip:Hide()
        end
    )

    button:SetScript(
        "OnUpdate",
        function()
            local ap, tp = GetPoints("ap"), GetPoints("tp")
            if ap > 0 or tp > 0 then
                FrameShow(button.flash)
            else
                FrameHide(button.flash)
            end
        end
    )

    button:RegisterEvent("PLAYER_LEVEL_UP")
    button:SetScript(
        "OnEvent",
        function()
            local tab = _G["CLMainFrame"]:GetAttribute("tab")
            if tab ~= 0 then
                SelectTab(tab, "CLContainer", "CLMainFrame", "CLButton")
            end
        end
    )

    --Create Main Frame
    local frame = CLMainFrame or CreateFrame("Frame", "CLMainFrame", UIParent)
    frame:Hide()
    frame:SetMovable(true)
    frame:EnableMouse(true)
    frame:SetToplevel(true)
	
	frame.titleRegion = frame:CreateTitleRegion()
	frame.titleRegion:SetSize(600, 24) -- 600 wide x 24 tall
	frame.titleRegion:SetPoint("TOPLEFT") -- anchor the titleRegion to top left of frame
	-- this is the drag bar texture
	frame.titleTexture = frame:CreateTexture("frame_titleTexture", "ARTWORK")
	frame.titleTexture:SetSize(600, 24)  -- texture is the same size as the title drag bar
	frame.titleTexture:SetPoint("TOPLEFT")
	
	
    frame:RegisterForDrag("LeftButton")
    frame:SetToplevel(true)
	frame:SetSize(440, 620) -- 420x680

    --FrameBackground(frame, "Interface\\QuestFrame\\UI-QuestLog-Empty")
    frame:SetPoint("CENTER", 0, 0)
    frame:SetScript("OnDragStart", frame.StartMoving)
    frame:SetScript("OnDragStop", frame.StopMovingOrSizing)
    frame:SetAttribute("tab", 0)
    frame:SetAttribute("child", "CLContainer")
	
	AIO.SavePosition(frame)
    
    local function TabSelect(frame)
        if (frame == nil) then frame = _G["CLMainFrame"] end
        local tab = frame:GetAttribute("tab")
        if tab == 0 then
                tab = 1
        end
        SelectTab(tab, "CLContainer", "CLMainFrame", "CLButton")
    end

    frame:SetScript(
        "OnShow",
        function()
            TabSelect()
        end
    )

    frame:SetScript(
        "OnHide",
        function()
            local tab = frame:GetAttribute("tab")
            if tab ~= 0 then
                FrameHide("Container" .. tab)
            end
        end
    )

    frame:SetScript(
        "OnUpdate",
        function()
            local ap, tap = GetPoints("ap")
            local tp, ttp = GetPoints("tp")
           -- local string =icons[1].."         "..icons[2].."\n"
			local string2 = ap
            if tap > 0 then
                string2 = string2 .. "(" .. tap .. ")"
            end
           -- string = string .. " AP "..icons[1].." " .. tp
			string2 = string2.." AP     " .. tp
            if ttp > 0 then
                string2 = string2 .. "(" .. ttp .. ")"
            end
            string2 = string2 .. " TP "--..icons[2]
            
            if _G["CLMainFramePoints"] ~= nil then
			--	CLMainFramePoints.text:SetText(string)
                CLMainFramePoints.text2:SetText(string2)
            end

            local tab = _G["CLMainFrame"]:GetAttribute("tab")
            if tab ~= nil and tab > 0 then
 
               if (tab == 1 and tap > 0) or (tab == 2 and ttp > 0) then
                    FrameShow("CLResetButtonFrame")
                else
                    FrameHide("CLResetButtonFrame")
                end
            end
        end
    )

    -- Close button
    local button = _G["CLMainFrameClose"] or CreateFrame("Button", "CLMainFrameClose", _G["CLMainFrame"])
    button:SetSize(32,32)
    button:SetPoint("TOPRIGHT",13,-82)
    button:SetNormalTexture("Interface\\Buttons\\UI-Panel-MinimizeButton-Up")
    button:SetPushedTexture("Interface\\Buttons\\UI-Panel-MinimizeButton-Down")
    button:SetHighlightTexture("Interface\\Buttons\\UI-Panel-MinimizeButton-Highlight")
    button:SetScript(
        "OnClick",
        function()
            FrameHide("CLMainFrame")
        end
    )

    -- AP and TP points
    local frame = _G["CLMainFramePoints"] or CreateFrame("Frame", "CLMainFramePoints", _G["CLMainFrame"])
    frame:SetSize(160, 32)
    frame:SetPoint("BOTTOMLEFT", 103, 95)
	
    frame.text = frame:CreateFontString("CLMainFramePointsText", "OVERLAY", "GameFontNormal")
	
	CLMainFramePoints.text:SetJustifyV("MIDDLE");
	CLMainFramePoints.text:SetJustifyH("LEFT");
    frame.text:SetPoint("TOPRIGHT")
	
	frame.text2 = frame:CreateFontString("CLMainFramePointsText2", "OVERLAY", "GameFontNormal")
	
	CLMainFramePoints.text2:SetJustifyV("MIDDLE");
	CLMainFramePoints.text2:SetJustifyH("LEFT");
    frame.text2:SetPoint("TOPRIGHT")
    --frame:SetScript("OnUpdate", UpdatePoints)

    -- Learn Confirm and cancel
    local frame = _G["CLResetFrame"] or CreateFrame("Frame", "CLResetFrame", _G["CLMainFrame"])
    frame:SetSize(160, 32)
    frame:SetPoint("BOTTOMLEFT", 200, -35)
    --frame:SetScript("OnUpdate", UpdateReset)

    local frame = _G["CLResetButtonFrame"] or CreateFrame("Frame", "CLResetButtonFrame", _G["CLResetFrame"])
    frame:Hide()
    frame:SetSize(160, 32)
    frame:SetPoint("CENTER")
    
    local frame = _G["CLWipeButtonFrame"] or CreateFrame("Frame", "CLWipeButtonFrame", _G["CLResetFrame"])
    frame:SetSize(32, 32)
    frame:SetPoint("BOTTOMRIGHT",0,0)

    local button =
    _G["CLWipeButton"] or
    NewButton(
        "CLWipeButton",
        _G["CLWipeButtonFrame"],
        36,
        "Interface\\Tooltips\\Reverse_White",
        nil
    )
    button:SetPoint("BOTTOMRIGHT", 70, 175)
    button:SetScript(
        "OnClick",
        function()
            if not (button:IsEnabled()) then return end
			 rst = db.reset + 1
            if (rst > #prices) then
                rst = #prices
            end
            StaticPopup_Show("WIPE_SPELLS",prices[rst])
        end
    )
	

    StaticPopupDialogs["WIPE_SPELLS"] = {
        text = "Confirm resetting your spells and talents\n\nCurrent price is: %s |TInterface/ICONS/INV_Misc_Apexis_Crystal:20:20|t",
        button1 = YES,
        button2 = NO,
        OnAccept = function()
            AIO.Handle(handlerName, "WipeAll", clientSecret)
            TabSelect()
            _G["CLMainFrame"]:Hide()
        end,
        timeout = 0,
        whileDead = true,
        hideOnEscape = true,
        preferredIndex = 3
    }
	
	

    local buttons = {"Learn", "Reset"}
    for i = 1, #buttons do
        --buttons:
        local button = _G["CLResetButton" .. i] or MakeButton("CLResetButton" .. i, _G["CLResetButtonFrame"])
        button:SetText(buttons[i])
        button:SetSize(80, 22)
        button:SetPoint("BOTTOMRIGHT", (81 * (i - 1)), 143) 
        button:SetScript(
            "OnClick",
            function()
				if not (button:IsEnabled()) then return end
                LearnConfirm(buttons[i], "false")
            end
        )
    end

    --Learn and reset confirm dialogs
    StaticPopupDialogs["LEARN_CONFIRM"] = {
        text = "Please, confirm learning.",
        button1 = "Yes",
        button2 = "No",
        OnAccept = function()
            LearnConfirm("Learn", "true")
        end,
        timeout = 0,
        whileDead = true,
        hideOnEscape = true,
        preferredIndex = 3
    }

    StaticPopupDialogs["RESET_CONFIRM"] = {
        text = "Please, confirm resetting.",
        button1 = "Yes",
        button2 = "No",
        OnAccept = function()
            LearnConfirm("Reset", "true")
        end,
        timeout = 0,
        whileDead = true,
        hideOnEscape = true,
        preferredIndex = 3
    }

    --Main Tab buttons, containers
    local buttons = {"", ""}
    for i = 1, #buttons do
        --buttons:
        local button = _G["CLButton" .. i] or MakeButton("CLButton" .. i, _G["CLMainFrame"])
        button:SetText(buttons[i])
        button:SetSize(46, 46)
        button:SetPoint("TOPRIGHT",50*(i)-105, -118)
        button:SetScript(
            "OnClick",
            function()
                SelectTab(i, "CLContainer", "CLMainFrame", "CLButton")
            end
        )
        --containers:
        local frame = _G["CLContainer" .. i] or CreateFrame("Frame", "CLContainer" .. i, _G["CLMainFrame"])
        frame:SetSize(_G["CLMainFrame"]:GetWidth() - 24, _G["CLMainFrame"]:GetHeight() - 96)
        frame:SetPoint("TOPLEFT", 12, -48)
        frame:SetAttribute("tab", 0)
        frame:SetAttribute("child", "CLContainer" .. i .. "Sub")
        frame:Hide()
        frame:SetScript(
            "OnShow",
            function()
                local tab = frame:GetAttribute("tab")
                if tab == 0 then
                    tab = 1
                end
                local child = frame:GetAttribute("child")
                SelectTab(tab, child, "CLContainer" .. i, child .. "Button")
            end
        )
    end

    
    local frame = _G["CLClassesFrame"] or CreateFrame("Frame", "CLClassesFrame", _G["CLMainFrame"])
    frame:SetSize(_G["CLMainFrame"]:GetWidth()/2.8, _G["CLMainFrame"]:GetHeight())
    frame:SetPoint("TOPRIGHT", frame:GetWidth()-200, 0)
   
		t = CreateTexture(frame, "BACKGROUND")
       t:SetPoint("CENTER",-40,10)
        frame.background = t
		frame.background:SetTexture("Interface\\Tooltips\\finalframe")
		frame.background:SetSize(_G["CLMainFrame"]:GetWidth()*1.1,_G["CLMainFrame"]:GetHeight()*0.79)

		

  -- frame:SetBackdrop(
     --   {
       --     bgFile = "Interface\\TutorialFrame\\TutorialFrameBackground",
          -- edgeFile = "Interface\\Tooltips\\UI-Tooltip-Border",
       --     tile = true,
       --     edgeSize = 16,
      --      tileSize = 16,
      --      insets = {left = 0, right = 0, top = 2, bottom = 2}
     --   }
 --   )

    for index = 1, 2 do
        --Class buttons for spells and talents
        local i = 1
        local arr
        local mode
        if index == 1 then
            arr = db.data.spells
            mode = "spell"
        else
            arr = db.data.talents
            mode = "talent"
        end
        for k, v in pairsSort(arr) do
            local class, cnum, inum = k, i, index
            local button =
                _G["CLContainer" .. index .. "SubButton" .. i] or
                NewButton(
                    "CLContainer" .. index .. "SubButton" .. i,
                    _G["CLContainer" .. index],
                    32,
                    "Interface\\GLUES\\CHARACTERCREATE\\UI-CHARACTERCREATE-CLASSES",
                    nil,
                    unpack(CLASS_ICON_TCOORDS[class])
                )
            local minusH = 61
            startPoint = (_G["CLContainer" .. index]:GetHeight() - (minusH*10)) / 2
            if(i <= 5) then
			button:SetPoint("TOPRIGHT", -330, -startPoint-minusH * (i -1)-185)--first 5
			else
			button:SetPoint("TOPRIGHT", 65, -startPoint-minusH * (i -1)+120)--second 5
			end
            button.text =
                _G["CLContainer" .. index .. "SubButton" .. i .. "Text"] or
                button:CreateFontString("CLContainer" .. index .. "SubButton" .. i .. "Text", "OVERLAY", "DialogButtonNormalText")
            button.text:SetPoint("LEFT", 40, 0)
            button.text:SetFont("Fonts\\MORPHEUS.TTF", 13, "")
           -- button.text:SetText(classes[i])
            button:SetScript(
                "OnClick",
                function()
					if not (button:IsEnabled()) then return end
                    SelectTab(
                        cnum,
                        "CLContainer" .. inum .. "Sub",
                        "CLContainer" .. inum,
                        "CLContainer" .. inum .. "SubButton"
                    )
					LastContainerNum = inum
                end
            )

            local frame =
                _G["CLContainer" .. index .. "Sub" .. i] or
                CreateFrame("Frame", "CLContainer" .. index .. "Sub" .. i, _G["CLContainer" .. index])
            --frame:SetBackdrop({bgFile="Interface\\Buttons\\WHITE8X8"})
            frame:SetSize(_G["CLContainer" .. index]:GetSize())
            frame:SetPoint("TOPLEFT")
            frame:SetAttribute("tab", 0)
            frame:SetAttribute("child", "CLContainer" .. index .. "Sub" .. i .. "Sub")
            frame:Hide()
            frame:SetScript(
                "OnShow",
                function()
                    local tab = frame:GetAttribute("tab")
                    if tab == 0 then
                        tab = 1
                    end
                    local child = frame:GetAttribute("child")
                    SelectTab(tab, child, "CLContainer" .. inum .. "Sub" .. cnum, child .. "Button")
                end
            )

            --buttons and containers for spells
            for j = 1, #arr[class] do
                local snum = j
                local button =
                    _G["CLContainer" .. index .. "Sub" .. i .. "SubButton" .. j] or
                    NewButton(
                        "CLContainer" .. index .. "Sub" .. i .. "SubButton" .. j,
                        _G["CLContainer" .. index .. "Sub" .. i],
                        36,
                        "Interface\\Icons\\" .. arr[class][j][2],
                        nil
                    )
                button:SetPoint("TOPRIGHT", 46 * (j) -250, -75)
                button:SetScript(
                    "OnClick",
                    function()
						if not (button:IsEnabled()) then return end
                        SelectTab(
                            snum,
                            "CLContainer" .. inum .. "Sub" .. cnum .. "Sub",
                            "CLContainer" .. inum .. "Sub" .. cnum,
                            "CLContainer" .. inum .. "Sub" .. cnum .. "SubButton"
                        )
                    end
                )

                local frame =
                    _G["CLContainer" .. index .. "Sub" .. i .. "Sub" .. j] or
                    CreateFrame(
                        "Frame",
                        "CLContainer" .. index .. "Sub" .. i .. "Sub" .. j,
                        _G["CLContainer" .. index .. "Sub" .. i]
                    )
                frame:SetSize(
                    _G["CLContainer" .. index .. "Sub" .. i]:GetWidth()+10,
                    _G["CLContainer" .. index .. "Sub" .. i]:GetHeight()+35
                )
                frame:SetPoint("BOTTOM",1,-40)
                FrameBackground(frame, "Interface\\TalentFrame\\" .. arr[class][j][3])
                FrameLayout(frame, frame:GetWidth(), frame:GetHeight()+45)
                frame:Hide()
                frame:SetScript(
                    "OnShow",
                    function()
                        local timer = GetTime()
                        frame:SetScript(
                            "OnUpdate",
                            function()
                                if GetTime() - timer >= 0.1 then
                                    FillSpells(class, snum, frame, mode)
                                    frame:SetScript("OnUpdate", nil)
                                end
                            end
                        )
                    end
                )
            end

            i = i + 1
        end
    end --end of index
end --End of Doshit

--Main Execution
local MyHandlers = AIO.AddHandlers(handlerName, {})


function MyHandlers.LoadVars(player, spr, tpr, tar,rem, rst, prc, scr, rsd)
    --Init
    db.spells = spr
    db.tpells = tpr
    db.talents = tar
    db.reset = rst
    prices = prc
    if (rsd ~= true) then
	    clientSecret = scr
        DoShit()
    end
end

--hook original Talent Frame
function ToggleTalentFrame()
    FrameToggle("CLMainFrame")
end
